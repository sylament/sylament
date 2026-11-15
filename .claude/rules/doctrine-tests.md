---
paths:
  - "packages/{actions,forms,panels,schemas,tables}/tests/**"
  - "packages/support/tests/Unit/Doctrine/**"
  - "packages/support/tests/Fixtures/Doctrine/**"
description: The database contract and the shim, helpers and fixtures the five Doctrine suites build on the shared rig.
---
# Doctrine test rules

- **Build a manager through the suite's shim,** `packages/<pkg>/tests/Fixtures/Doctrine/EntityManagerFactory::create()`, which delegates to `TestEntityManagers`; a manager built by hand misses the pool, the foreign keys and the custom types.
- **The database contract:** every manager a test obtains starts empty, identity counters included, and isolated from the test's other managers, on every platform; write no test that leans on rows or ids a previous test left.
- **SQLite enforces foreign keys** (the `EnableForeignKeys` middleware), as MySQL and PostgreSQL do, so a test that passes on SQLite is not hiding an orphan the servers would refuse.
- **Seed through `EntityManagerFactory::seed()`,** which flushes and then clears the identity map, so the next read goes to the database rather than answering from memory.
- **Reset a `RecordingMiddleware` after seeding** so only the statements under test count; the recorder never sees a wipe, a schema build or a `PRAGMA`, so it needs no filtering of those.
- **Pin a statement through `SqlNormalizer::normalize()`,** compare aggregate values through `DriverValues::numeric()`, and make a write fail on purpose with `FailingWrite::afterDeletes()`; each already covers the platform differences a hand-written version would miss.
- **A premise that compares platforms** takes a manager pinned to SQLite through `TestEntityManagers::createSqlite()` (schemas: `createForSqlite()`), whatever the environment names.
- **A fixture column whose custom type extends `StringType` declares `length:`,** because MySQL refuses a `VARCHAR` without one.
- **Server runs are serial;** `TEST_TOKEN` separates two processes' pool databases, not their connection budget. The commands and setup are in the `server-gates` skill.

Background: `docs/internals/test-infrastructure.md`.
