---
paths:
  - "/tests/Fixtures/src/Entity/**"
  - "/tests/Infrastructure/Doctrine/**"
  - "/tests/Fixtures/src/*/Doctrine/**"
  - "/tests/Fixtures/config/packages/doctrine.php"
description: How the root suite prepares its database, where its entities and Doctrine fixtures live, and how it counts statements and cleans up after a server test.
---
# Test database rules

- **Leave preparation to the first connection:** `TestDatabaseMiddleware` has `TestDatabase` prepare once per test (SQLite copies a template over the worker's file, a server has the pool wipe the worker's `<base>[_<run>]_suite_<TEST_TOKEN>`), so a test that never connects pays nothing; never prepare, wipe or build a schema from a test.
- **Put every entity in the shared `src/Entity/`,** which `config/packages/doctrine.php` maps as one folder: reuse one before adding one, and never change one another test reads, since every area's tests share it; add an entity for a shape none has.
- **A fixture column whose custom type extends `StringType` declares `length:`,** because MySQL refuses a `VARCHAR` without one.
- **Count statements through DoctrineBundle's profiling** (`profiling: true`, read by `statementsRun()`), never a recorder of the suite's own, and keep `TestDatabaseMiddleware` and `EnableForeignKeys` (innermost, SQLite only) above its priority 10: DoctrineBundle wraps the driver from the highest priority out, so this keeps the preparation and every `PRAGMA` out of the count.
- **A test that adds a trigger or helper table on a server** registers its removal with `DatabasePool::cleanUpBeforeNextWipe()` under its connection's database, as `FailingWrite` does; a wipe empties tables and drops nothing.

Background: `docs/internals/test-infrastructure.md`.
