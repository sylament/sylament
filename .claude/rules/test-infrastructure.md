---
paths:
  - "/tests/Fixtures/**"
  - "/tests/Infrastructure/**"
  - "/tests/TestCase.php"
  - "/tests/Pest.php"
  - "/phpunit.dist.xml"
description: How the root suite's kernel, configuration, fixture areas, writable paths, database and base class are laid out and kept parallel-safe.
---
# Test infrastructure rules

- **One kernel, one base class.** `phpunit.dist.xml` names `Sylament\Tests\Fixtures\Kernel` and `tests/Pest.php` extends `Sylament\Tests\TestCase` over the whole tree; never add a second of either, because a test on its own kernel exercises a world no other test sees.
- **Area ownership is by directory.** A package's area owns `tests/<Area>/`, `tests/Fixtures/<Area>/`, `tests/Fixtures/templates/<area>/`, `tests/Fixtures/icons/<area>/`, `tests/Fixtures/config/routes/<area>.php` and `tests/Fixtures/config/packages/<area>.php`; everything else under `tests/` is shared, so two areas never edit one file.
- **Create a fixture in the lowest area that needs it** (support, schemas, forms, actions, tables, panels); a higher area uses it and never edits it, and adds its own when it needs something different.
- **Never edit the `Kernel` to register a fixture.** `config/services.php` loads `Sylament\Tests\Fixtures\` from `tests/Fixtures/` with autowire and autoconfigure, so a class dropped into an area folder is registered; fixtures stay private, since `test.service_container` reaches them.
- **Exclude a non-service directory from that glob** as `config/`, `templates/`, `icons/`, `assets/`, `importmap.php` and `*/Entity/` are, or the loader registers what it finds there.
- **Configure through files, one per extension:** `config/packages/<extension>.php`, `config/packages/<env>/*.php`, `config/services.php` and `config/routes/*.php`, the layout `MicroKernelTrait` imports from the kernel's config directory; the `Kernel` keeps only the bundle list, the config directory (`getConfigDir()`), the writable paths, the kernel parameters it adjusts (`getKernelParameters()`) and the route order.
- **Keep the route order:** `routes/live_component.php` (live routes under `/_components`), then the panel route loader, then every other `routes/*.php`; the kernel imports them in that order rather than by name.
- **Register every bundle in every environment.** An environment exists only for a configuration value that would change what other tests observe, is named after what it varies, and keeps its files in `config/packages/<env>/`; it never changes the bundle list.
- **Leave debug on** (`APP_DEBUG` unset), so a worker recompiles its container when a fixture changes.
- **Name templates and icons by area:** a template is `@Fixtures/<area>/…` and an icon `<area>:<name>` from `icons/<area>/`; one Twig namespace and one icon directory serve every area.
- **Write only under `WorkerDirectory::path()`** (`var/tests/<TEST_TOKEN or 0>`): the kernel's cache and logs, the session files, the SQLite files under `database/`, and the `primary.storage` and `secondary.storage` disks under `storage/`, reached in config as `%sylament_tests.worker_dir%`; never `sys_get_temp_dir()`, a fixed path or a source directory, because parallel workers would share it.
- **Resolve what differs per run at runtime:** the connection URL reaches the container as `%env(SYLAMENT_TEST_CONNECTION_URL)%`, which `TestDatabaseExtension` publishes in every process, and the cache directory is `cache/<env>/<platform>`, because a compiled container never notices that an environment variable changed.
- **`TestCase::tearDown()` is the reset:** it unsets every loaded `StaticInstance`, booted or not, and removes the worker's `storage/`; never add an `afterEach` that repeats it.
- **No free helper functions.** Every test file outside `tests/Architecture/` shares the global namespace, so two areas defining one name collide; a helper that needs the container is a `TestCase` method from a trait (a shared one in a `Concerns/` folder of `tests/Infrastructure/`, an area's own `<Area>Helpers` in its fixture folder, each with one `use` line in `TestCase`), and a pure one is a static method.
- **Keep classes out of Pest's auto-loaded folders** (`Expectations/` and `Helpers/` in `tests/`): Pest `include_once`s every file there at boot and Composer autoloads the class again, so it is declared twice.
- **One database per test:** every manager the kernel's `doctrine` registry holds, in-process or in any request, works on the test's one database, which starts empty with fresh identity counters on SQLite, MySQL and PostgreSQL; never lean on rows or ids another test left.
- **Keep the client's reboot on** (Symfony's default), so requests share no memory, as in production; `TestDatabase` keeps the database's state per process, so a rebooted kernel still finds the test's rows.
- **Leave preparation to the first connection:** `TestDatabaseMiddleware` has `TestDatabase` prepare once per test (SQLite copies a template over the worker's file, a server has the pool wipe the worker's `<base>_suite_<TEST_TOKEN>`), so a test that never connects pays nothing; never prepare, wipe or build a schema from a test.
- **Fetch the manager on every use** through `$this->entityManager()`, and never keep one across a request: after a request it belongs to the rebooted kernel.
- **Seed with `$this->seed()`,** which flushes, clears and resets the recorder, so the next read goes to the database and `$this->statementsRun()` holds only the statements under test, counted from the last `seed()` or kernel reboot, whichever came later, so assert a count within one request; read a record back with `$this->reload()`, which re-reads its row even when the manager already holds it.
- **Map an entity by its folder:** `config/packages/doctrine.php` maps every `<Area>/Entity/` under its namespace, so an area edits no config; an entity's short name is its table name and stays unique across areas, since a clash fails the schema build.
- **Keep the middleware order:** `RecordingMiddleware` outermost, `TestDatabaseMiddleware` inside it, `EnableForeignKeys` innermost and on SQLite only; DoctrineBundle wraps the driver from the highest priority out, and this order keeps the preparation and every `PRAGMA` out of the recorder.
- **Read the recorder from the connection,** as `InteractsWithDatabase` does, never by class: DoctrineBundle builds one per connection from the abstract definition.
- **A test that adds a trigger or helper table on a server** registers its removal with `DatabasePool::cleanUpBeforeNextWipe()` under its connection's database, as `FailingWrite` does; a wipe empties tables and drops nothing.
- **Skip a subscriber's registration in Pest's parallel coordinator** (`Parallel::isEnabled() && !Parallel::isWorker()`), as `TestDatabaseExtension` does: the coordinator bootstraps extensions after sealing PHPUnit's event facade and runs no test, and the warning recorded there fails the run without being printed.
- **Never import `tests/Infrastructure/` from any `src/`:** the Doctrine rig (`Sylament\Tests\Infrastructure\Doctrine\`, `\PHPUnit\`) is dev-only, because the root `tests/` belongs to no package and ships in none, so a `src/` import of it fatals in every split install.
- **Infrastructure and fixtures are analysed like shipped code:** PHPStan covers `tests/` at level max except every `*Test.php` under `tests/`, `tests/Pest.php` and its `Expectations/` folder, and a fixture deliberately ill-typed for the behaviour it exercises takes an identifier-scoped `@phpstan-ignore` with its reason.
- **Run the suite as CI does:** `composer test:pest` runs it `--parallel`, so a race shows the day it is written.

Background: `docs/internals/test-infrastructure.md`.
