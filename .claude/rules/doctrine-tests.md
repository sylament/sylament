---
paths:
  - "packages/{actions,forms,panels,schemas,tables}/tests/**"
  - "packages/support/tests/Doctrine/**"
  - "packages/support/tests/PHPUnit/**"
  - "packages/support/tests/Unit/Doctrine/**"
  - "packages/support/tests/Fixtures/Doctrine/**"
description: The database contract, the platform rule and the shared Doctrine test infrastructure the five Doctrine suites run on.
---
# Doctrine test rules

- **The shared infrastructure** lives in `packages/support/tests/Doctrine/` and `tests/PHPUnit/` (`Sylament\Support\Tests\Doctrine\`, `\PHPUnit\`); never import it from any `src/`, because support's `.gitattributes` export-ignores `/tests` and a split package would fatal.
- **It is analysed like shipped code:** `phpstan.neon.dist` names both directories at level max, unlike every other test file.
- **Build a manager through the suite's shim,** `tests/Fixtures/Doctrine/EntityManagerFactory::create()`, which delegates to `TestEntityManagers`; a manager built by hand misses the pool, the foreign keys and the custom types.
- **The database contract:** every manager a test obtains starts empty, identity counters included, and isolated from the test's other managers, on every platform; write no test that leans on rows or ids a previous test left.
- **SQLite enforces foreign keys** (the `EnableForeignKeys` middleware), as MySQL and PostgreSQL do, so a test that passes on SQLite is not hiding an orphan the servers would refuse.
- **Seed through `EntityManagerFactory::seed()`,** which flushes and then clears the identity map, so the next read goes to the database rather than answering from memory.
- **Reset a `RecordingMiddleware` after seeding** so only the statements under test count; the recorder never sees a wipe, a schema build or a `PRAGMA`, so it needs no filtering of those.
- **Pin a statement through `SqlNormalizer::normalize()`,** compare aggregate values through `DriverValues::numeric()`, and make a write fail on purpose with `FailingWrite::afterDeletes()`; each already covers the platform differences a hand-written version would miss.
- **The platform rule.** A test runs on SQLite, MySQL and PostgreSQL, or is skipped by premise with `->skip(<condition>, '<the platform fact>')` where its premise cannot exist on one.
- **Write a differing expectation literally per platform** with `match (TestPlatform::current())`, never computed by a helper that mirrors the product, which would pass whatever the product does.
- **A premise that compares platforms** takes a manager pinned to SQLite through `TestEntityManagers::createSqlite()` (schemas: `createForSqlite()`), whatever the environment names.
- **A test that adds its own trigger or helper table on a server** registers its removal with `DatabasePool::cleanUpBeforeNextWipe()`, as `FailingWrite` does for its trigger; the pool reuses the database for the next test.
- **A fixture column whose custom type extends `StringType` declares `length:`,** because MySQL refuses a `VARCHAR` without one.
- **Server runs are serial;** `TEST_TOKEN` separates two processes' pool databases, not their connection budget. The commands and setup are in the `server-gates` skill.

Background: `docs/internals/test-infrastructure.md`, `docs/progress/schemas/search-constraint.md`, `docs/progress/actions/delete-action.md`, `docs/progress/schemas/record-key.md`.
