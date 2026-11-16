---
paths:
  - "packages/{actions,forms,panels,tables}/tests/**"
description: The database contract and the shim, helpers and fixtures the four Doctrine suites build on the shared rig.
---
# Doctrine test rules

- **Build a manager through the suite's shim,** `packages/<pkg>/tests/Fixtures/Doctrine/EntityManagerFactory::create()`, which delegates to `TestEntityManagers`; a manager built by hand misses the pool, the foreign keys and the custom types.
- **The database contract:** every manager a test obtains starts empty, identity counters included, and isolated from the test's other managers, on every platform; write no test that leans on rows or ids a previous test left.
- **SQLite enforces foreign keys** (the `EnableForeignKeys` middleware), as MySQL and PostgreSQL do, so a test that passes on SQLite is not hiding an orphan the servers would refuse.
- **Seed through `EntityManagerFactory::seed()`,** which flushes and then clears the identity map, so the next read goes to the database rather than answering from memory.
- **Reset a `RecordingMiddleware` after seeding** so only the statements under test count; the recorder never sees a wipe, a schema build or a `PRAGMA`, so it needs no filtering of those.
- **Pin a statement through `SqlNormalizer::normalize()`,** so the pin survives a Doctrine release that renumbers the aliases it mints.
- **Server runs are serial;** `TEST_TOKEN` separates two processes' pool databases, not their connection budget. The commands and setup are in the `server-gates` skill.

Background: `docs/internals/test-infrastructure.md`.
