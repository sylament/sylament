# Test infrastructure

The reasoning behind the test rules in `.claude/rules/test-infrastructure.md`, `.claude/rules/legacy-tests.md` and `.claude/rules/doctrine-tests.md`; the rules for writing a test, `.claude/rules/tests.md` and `.claude/rules/test-scope.md`, are explained in [testing.md](testing.md). The rules say what to do; this file says why, and how the machinery underneath works. The commands for running the suites on a server are in the `server-gates` skill.

Two kinds of suite run side by side: the root suite under `tests/`, one kernel and one base class for every package, and the per-package suites under `packages/<pkg>/tests/`, each with its own kernel. The first sections describe the root suite, the middle ones the Doctrine rig both kinds share, and the last, under [The per-package suites](#the-per-package-suites), what only those suites need.

## Why does the root suite have one kernel and one base class?

The per-package suites grew a fixture kernel each, and they disagree: different default disks, a session in some and not others, asset mapper in one, icon directories whose same-named icons differ, live routes prefixed in one kernel only. A test passing on its own package's kernel says nothing about the others. The root suite resolves each conflict once, in `Sylament\Tests\Fixtures\Kernel`, and every test runs against that one world:

- a `mock_file` session, because a toast is a session flash;
- asset mapper with an importmap declaring the `app` entrypoint the panels' document layout imports, plus `@TwigBundle/Resources/config/importmap.php`, because TwigBundle registers `importmap()` behind a `willBeAvailable()` check that refuses `symfony/asset-mapper` as a dev-only package beside a production `symfony/twig-bundle`, which is this monorepo's shape and no application's;
- one icon directory with a subfolder per area, since UX Icons maps the `<area>:` prefix of `<area>:<name>` to that subfolder;
- one Twig namespace, `@Fixtures`, with a folder per area;
- two local disks, `primary.storage` (public, with a `public_url`, so it can mint a URL) and `secondary.storage` (the no-URL and foreign-disk arms), and `primary.storage` as the default disk;
- every bundle, Sylament's nine included, in every environment, so no test depends on a bundle being absent.

`Sylament\Tests\TestCase` extends `KernelTestCase` and uses UX's `InteractsWithTwigComponents`, tables' `InteractsWithLiveComponents`, the shared `InteractsWithDatabase` and `InteractsWithRendering`, and the notifications area's `NotificationsHelpers`. `KernelTestCase::getContainer()` boots on demand and its teardown shuts down only a booted kernel; `tests/Pest.php` asks for the container in a `beforeEach` over `tests/Packages/`, so every package test starts on a booted kernel and a component it makes anywhere, in a helper's argument included, is container-built, and there is no separate base class for pure tests. A test outside `tests/Packages/` asks for the container itself.

## How is the kernel configured?

`MicroKernelTrait` already imports `packages/*.php`, `packages/<env>/*.php`, `services.php` and `routes/*.php` from the kernel's config directory. The kernel points `getConfigDir()` at `tests/Fixtures/config/` and keeps the trait's `configureContainer()`, so the configuration is Symfony's own file layout, one file per extension. The class itself keeps what a file cannot: the bundle list, the writable paths, the route order, and the component-list pass, and the compile-only read behind it (below).

An environment changes one configuration value every other test would otherwise observe, in its own `packages/<env>/` folder. `test_mode_off` turns `framework.test` off, so a test can read what the container holds outside test mode, such as the test-only services support's `RemoveTestOnlyServicesPass` removes; no test boots it, since `KernelTestCase::getContainer()` needs the `test.service_container` that mode lacks, so it is read compile-only.

`services.php` registers `Sylament\Tests\Fixtures\` by glob with autowire and autoconfigure, so a class placed in an area's fixture folder is its own registration. The glob excludes the directories that hold no services, and entity folders, which are mapped by Doctrine rather than registered. It needs no exclude for a trait, an interface, an abstract class or an enum: Symfony's loader skips a trait and registers an interface or an abstract class as an abstract definition tagged `container.excluded`, and autoconfiguration tags an enum `container.excluded` too, so none can become a service and compilation removes each.

The routes are imported in a fixed order: the live routes under `/_components`, then the panel route loader, then every other `routes/*.php`. UX's live route is `/{_live_component}/{_live_action}`, which would match any two-segment fixture path or panel URL if it were mounted at the root; the recipe's prefix keeps it out of the way. A glob import sorts files by name, so the kernel imports `routes/live_component.php` itself and excludes it from the glob that follows.

In debug mode FrameworkBundle dumps a `reference.php` into the kernel's config directory on every container build. Here that directory is source, shared by every worker and every environment, and the dumped content differs per environment, so two environments would keep rewriting it and invalidating each other's container. The kernel drops the `.kernel.config_dir` parameter, which only that dump reads, and the file is never written.

The `@Fixtures` folder exists even before an area adds a template, because Twig's `FilesystemLoader::addPath()` throws on a missing directory the moment the Twig environment is built.

## Why is every writable path per worker?

`pest --parallel` runs each worker as its own process with its own `TEST_TOKEN`. `WorkerDirectory::path()` answers `var/tests/<TEST_TOKEN or 0>`, or `var/tests/<run>/<TEST_TOKEN or 0>` when `SYLAMENT_TEST_RUN` names a run, and everything the suite writes lives under it: the compiled container and the router's cache files (the build directory defaults to the cache directory), the logs, the session files, the SQLite files under `database/`, and the disks under `storage/`. The cache directory is `cache/<env>/<platform>` (see the database section below). The kernel exposes the worker directory as the `sylament_tests.worker_dir` parameter for configuration files. Two workers compiling one container into one directory corrupt each other's fixed-name files, which is why nothing writes to `sys_get_temp_dir()` or a fixed path. `composer test:pest` runs in parallel from the start, so a race shows on the day it is written.

The token separates the workers of one run, not two runs: every run numbers its workers from 1, so two runs in one checkout, which agents and workflows share by default, would compile into, copy over and wipe the same directories and databases, and each would fail the other's tests at random, with failures that look like product bugs. A second run therefore sets `SYLAMENT_TEST_RUN` (1 to 16 characters of `a-z`, `0-9` and `_`, so it is safe in a path and a database name), which adds its segment to the worker directory and to every database the pool names. A run that forgets it is stopped rather than trusted: in every process that runs tests, `TestDatabaseExtension` takes an exclusive, non-blocking `flock` on the worker directory's `.lock` and holds it until the process ends, and a process that finds it held prints the message naming the variable and exits before its first test. It exits instead of throwing because PHPUnit turns an exception thrown while an extension bootstraps into a warning and runs every test anyway. Pest's parallel coordinator runs no test and takes no lock. The lock covers only the worker directory: Paratest keeps its workers' status and result files in the system temporary directory, named by `uniqid()`.

Debug stays on: a worker recompiles its container when a fixture changes, and Symfony checks freshness once per process. Two edits escape that check. ux-icons keeps each icon it renders in a cache pool under the kernel's cache directory, keyed by the icon's name, so an edited fixture SVG renders its old markup until the worker's `cache/` under `var/tests/` is removed; a new SVG has no entry yet and is read on first use. The other is an edit inside `Kernel::build()` or a compiler pass (below).

## Why does `tearDown()` reset every static instance?

A pure test still reaches the static instances: `make()` falls back to `new` when no kernel is booted, and `configureUsing()` resolves a `ComponentManager` of its own. So `TestCase::tearDown()` calls `unsetInstance()` on every loaded class implementing `StaticInstance` after every test, booted or not. The list comes from `get_declared_classes()`, recomputed only when the count of declared classes changes: a class never loaded holds no instance, so the loaded classes are the whole list, and no kernel has to boot to name them. A new static instance is therefore reset without anyone editing a list. `TestCaseTest` pins it: a `configureUsing()` closure registered in one case is gone in the next. Its second case is meaningful only when it runs after the first in the same process, so a filtered run of that case alone passes vacuously. The same teardown removes the worker's `storage/`, so no upload survives into the next test.

## How does a test get every component that offers something?

A case that must run over every component offering a behaviour takes its dataset from `ComponentRegistry` (why a list is derived rather than typed is in [testing.md](testing.md#why-is-a-list-of-components-derived)). The components are the services tagged `sylament.component`, which support's bundle adds by autoconfiguration, and a compiled container keeps no tags. So `ComponentListPass`, which the kernel's `build()` adds, exports their classes as the `sylament_tests.components` parameter: sorted, without duplicates, and without `Sylament\Tests\` classes, since a fixture subclass of a component is no component a user gets.

Pest resolves a dataset while it collects the suite, before any test or `$this` exists, so the registry cannot ask a test for the container. It reads the parameter once per process and memoises it, from a kernel of its own that compiles (and on a cold cache warms) its container without booting it: `Kernel::getCompiledContainer()` runs the kernel's `initializeBundles()` and `initializeContainer()` and stops there. Booting would run every bundle's `boot()`. Support's binds the static instances, and FrameworkBundle's registers Symfony's error handler. In Pest's parallel coordinator and workers no error handler is installed while the suite is collected, so Symfony's would stay installed as the process's error and exception handler, and the first test would start with both. Compiling alone leaves neither, and costs one container compile, cache warmers included, on a cold cache. What does outlive the read is harmless, and includes Symfony's per-process note that the cached container is fresh (`AbstractKernel::$freshCache`) and the registry's own memo. A process that collects no derived dataset never reads the parameter.

The datasets are keyed by short class name, so a test reads as "with dataset \"TextInput\"". Two components sharing a short name in one result throw rather than one silently replacing the other; `Sylament\Schemas\Components\Group` and `Sylament\Tables\Grouping\Group` already share one.

`tests/Infrastructure/ComponentRegistryTest.php` pins the residue: after the list is read, no static instance is bound. The case resets the registry's memo through reflection before it reads, because once any earlier read has memoised the list the case would read nothing, and an earlier test's `tearDown()` has already unset every static instance, so it would pass whatever the registry did. The same file pins `componentsUsing()` through a trait that `Select` composes by way of another trait and `TextInput` does not use.

Symfony checks the kernel class and each compiler pass for changes by their signatures, not their method bodies, so an edit inside `Kernel::build()` or `ComponentListPass::process()` reaches a worker's cached container only after that worker's `cache/` under `var/tests/` is removed.

## Why are there no free helper functions?

Every root-suite test file shares its namespace with the others (the global one, or the scans' `Sylament\Tests\Architecture`), and the per-package suites already define eight helper names in two or more places (`renderToHtml`, `registryWith`, …), which would collide. A function defined in one test file and called from another also breaks under `--parallel`, where the defining file may not have loaded in that worker. Helpers that need the container are methods of `TestCase` from traits, and pure ones are static methods, both of which PHPStan sees. Pest `include_once`s every file under `tests/Expectations/` and `tests/Helpers/` at boot, and Composer autoloads a class again, so a class placed there is declared twice.

## What does PHPStan analyse in the root suite?

Everything under `tests/` except every `*Test.php` file in it, `tests/Pest.php` and `tests/Expectations/`. PHPStan types a Pest closure's `$this` as `Pest\PendingCalls\TestCall`, so `$this::getContainer()` fails at any level, and custom expectations are reached through `__call`. The kernel, its configuration, the infrastructure and the fixtures are ordinary PHP and are held to level max like shipped code.

## How does the root suite give every test one database?

Every manager the kernel's `doctrine` registry holds, in the test itself or in any request it sends, works on one database, and that database starts each test empty, identity counters included, on SQLite, MySQL and PostgreSQL. The per-package suites give each manager a database of its own, which suits a manager built by hand; the root suite's managers come from DoctrineBundle and a request runs on its own container, so a request has to see what the test seeded.

### Configuration

`tests/Fixtures/config/packages/doctrine.php` configures DoctrineBundle:

- One `default` connection, whose URL the container reads at runtime from `SYLAMENT_TEST_CONNECTION_URL`. `TestDatabaseExtension` publishes it in every process, the parallel workers included, before any test is collected: on SQLite the worker's `database/test.sqlite`, on a server the URL `SYLAMENT_TEST_DATABASE_URL` names with its database renamed `<base>[_<run>]_suite_<TEST_TOKEN or 0>`. A value read through `getenv()` while the container compiles is not a tracked resource, so a compiled container would keep a URL after the environment changed; the kernel's cache directory is keyed by platform as well, because `EnableForeignKeys` is registered only on SQLite.
- The ORM in `entity_managers:` form, so an area can add a named manager without restructuring the file. Its mappings are globbed: one `attribute` mapping per `tests/Fixtures/<Area>/Entity/`, with the area's namespace as prefix. The services glob excludes an `Entity/` folder once it exists and stops watching it, so a `GlobResource` over the folders is what recompiles the container when one appears or goes; without it, removing a folder left a container mapping a missing directory.
- One metadata cache for the whole process, `TestDatabase::metadataCache()`. DoctrineBundle's default pool lives in each container, so every boot would read the attributes again; `cache.system` lives on disk and would outlast an entity edit. The service is not shared, because `Container::reset()`, which `KernelTestCase` runs at every kernel shutdown, resets every shared service it holds, and that would empty the cache. It deep-clones what it stores and what it hands out (`ArrayAdapter`'s default), so no two managers share a `ClassMetadata` object. Query and result caches, filters, hints and listeners stay per container, because tests change them. Metadata is cached after the `loadClassMetadata` listeners ran, though, so a test that varies a class's metadata through such a listener needs a manager with a metadata cache of its own.
- The second-level cache, on with an in-memory region cache per container; it caches only entities marked `#[ORM\Cache]`.
- DBAL's logging and profiling, off, so no container collects every statement it runs.
- Custom DBAL types go under `dbal.types`, in the area's own config file.

### Lifecycle

The client reboots the kernel between requests, Symfony's default, so each request after the first builds a new container and a new manager, as production does. If building a manager prepared the database, the second request would erase the test's seed. The state therefore lives in the process, in `TestDatabase`, never in a container service:

1. At each test's start, the extension's subscriber calls `TestDatabase::testStarted()`, which closes the connections the kernel opened in the previous test and marks the database stale. DoctrineBundle already closes every connection when its kernel shuts down, which `KernelTestCase` does after every test; this closes one reopened afterwards.
2. `TestDatabaseMiddleware` wraps the driver, and its `connect()` has `TestDatabase` prepare the database before connecting, once per test. On SQLite that deletes the worker's `test.sqlite` with its `-journal`, `-wal` and `-shm` sidecars, since a stale one would replay onto the fresh copy, and copies the template over it. On a server the pool wipes the worker's database, after running the clean-ups queued under its name. DBAL connects lazily, so building a manager prepares nothing, and a test that never connects pays nothing.
3. The schema is built once per process, at the first preparation: from every manager the registry holds, through a manager built over that manager's configuration and event manager on a connection of `TestDatabase`'s or the pool's own. Neither the recorder nor the connecting connection sees it, and preparing never reaches the connection being opened; a nested preparation throws rather than recursing. On SQLite the template goes to the worker's `database/template.sqlite`, deleted and rebuilt by every process, never reused from an earlier run; on a server the pool drops and creates the worker's database and builds it there.
4. `entityManager()` fetches from the current container on every call, so after a request it answers the rebooted kernel's manager.

`tests/Infrastructure/Doctrine/TestDatabaseTest.php` pins the lifecycle: a seed survives two requests through a rebooting client, and the next test starts empty with an identifier of 1. Its second case means something only after the first, in the same process. Its third crosses the boundary inside one case, calling `testStarted()` as the subscriber does, because between two real tests the kernel's shutdown already closes the connection and would hide a `testStarted()` that closed nothing; `DatabasePoolTest` and `FailingWriteTest` cross it the same way.

### Platform-pinned managers

A premise that compares platforms, such as a shape check that only PostgreSQL's guid type makes necessary, needs a manager whose platform the test names rather than the run. `InteractsWithDatabase::platformEntityManager()` builds one over the world's mapping:

- **SQLite** answers a manager on an in-memory database of its own, its schema built by `TestDatabase::buildSchema()`, the same build the world's preparation runs, and `EnableForeignKeys` added by hand, because `doctrine.php` registers it only on a SQLite run.
- **MySQL and PostgreSQL** answer a manager over a connection that never opens: told its server version, DBAL names the platform without connecting, which is all a platform read needs, so nothing on it may run a statement.

Each clones the world's ORM configuration and shares nothing the world could see. Its connection carries neither the recorder nor `TestDatabaseMiddleware`, so its statements stay out of `statementsRun()` and it never prepares the world's database. It has a metadata cache of its own, because loaded metadata can depend on the platform (the identifier strategy `AUTO` resolves to) and the shared cache would hand a manager on one platform another's. The second-level cache is off, since its regions belong to the world's container, and the repository factory is Doctrine's default, since a repository service answers for the world's manager whatever manager asked for it.

`tests/Infrastructure/Concerns/InteractsWithDatabaseTest.php` pins the isolation, the recorder, the separate caches and repository factory, the foreign keys and the platform reads without a connection, on all three gates.

### SQLite runs as a file

The test connection turns off `synchronous` and keeps its journal in memory, because the file lives for one test. Measured, 200 cycles of preparing, seeding one row and reading it back took 78.5 ms with SQLite's defaults and 41.3 ms with both pragmas. The middleware runs them after connecting, below the recorder.

### The middleware order

DoctrineBundle sorts the `doctrine.middleware` services by descending priority, and DBAL wraps the driver in that order, so the highest priority is the innermost layer. `EnableForeignKeys` (SQLite only) sits innermost, `TestDatabaseMiddleware` in the middle and `RecordingMiddleware` outermost, so the recorder sees neither a preparation nor a `PRAGMA`. DoctrineBundle builds one instance of each per connection from the registered definition, which is therefore abstract: a recorder fetched by its class would be a different object that never records. `InteractsWithDatabase` reads it from the connection's configuration instead.

### Why the extension skips Pest's parallel coordinator

Pest's parallel coordinator seals PHPUnit's event facade before it bootstraps the configured extensions, so registering a subscriber there throws. PHPUnit records the throw as a warning, and Pest fails the run without printing it. The coordinator runs no test, so `TestDatabaseExtension` registers nothing there, detected by Pest's own `Parallel::isEnabled() && !Parallel::isWorker()`; the workers carry ParaTest's `PARATEST=1` and bootstrap before sealing. Catching the throw instead would also hide a sealed facade in a process that does run tests.

Booting the kernel inside a test needs no error-handler bootstrap: FrameworkBundle registers Symfony's error handler without replacing one, and finding PHPUnit's in place, it restores both handlers, so PHPUnit sees nothing left behind.

## Why does the Doctrine rig live in the root suite's infrastructure?

Five suites run against Doctrine (`actions`, `forms`, `panels`, `schemas`, `tables`) and share one rig. It sits in `tests/Infrastructure/Doctrine/` and `tests/Infrastructure/PHPUnit/` (`Sylament\Tests\Infrastructure\Doctrine\`, `\PHPUnit\`), beside the root suite's own infrastructure, and the per-package suites import it from there. The root `composer.json` `autoload-dev` entry for `Sylament\Tests\` covers it, so no new autoload entry or package was needed.

It is dev-only. The root `tests/` belongs to no package, so none of it ships in a split package, and nothing under any `src/` imports it. Two consequences follow:

- PHPStan analyses them at level max like shipped code, because they sit under `tests/`, whose infrastructure it covers. No file under `packages/*/tests/` is analysed.
- The architecture tests that scan `packages/*/src` (`StaticAnalysisSuppressionTest`, `DbalParameterTypeDeclarationTest`, `AssociationReaderConstructionTest`) do not see it. The infrastructure is free to use what those checks forbid in shipped code.

The root suite pins the pieces it keeps at their mirrored paths (`tests/Infrastructure/Doctrine/<Class>Test.php`), against the world's database, each only for the behaviour the root suite relies on; `TestEntityManagers` and the pool's per-manager databases get no root pin, since only the per-package suites use them.
Support's own suite also tests the rig: its tests sit in `tests/Unit/Doctrine/` and its fixtures in `tests/Fixtures/Doctrine/`, mirroring the infrastructure rather than `src/`. Support's fixture kernel boots no Doctrine, so it excludes `Fixtures/Doctrine` from its service glob. Support's `phpunit.dist.xml` registers the pool extension too, because those tests exercise the pool.

## What is in the rig?

| Piece | Job |
|---|---|
| `TestPlatform` | Reads `SYLAMENT_TEST_DATABASE_URL`: unset or empty means SQLite (in memory for the per-package suites, a file for the root suite), `mysql://` or `postgresql://` (or `pgsql://`) names a server, any other scheme throws. |
| `TestEntityManagers` | Builds a package's manager. On SQLite it creates the schema in memory; on a server it asks the pool. `createSqlite()` always answers SQLite. It registers custom types once per process and takes an optional configuration callback. |
| `DatabasePool` | Hands a server test the databases SQLite would give it (below), and the root suite its one database per worker (`prepareWorld()`, `worldName()`). `cleanUpBeforeNextWipe()` queues statements to drop a test's own trigger or helper table. |
| `DatabasePoolExtension`, `DatabasePoolTestStarted` | The PHPUnit extension and the subscriber it registers, telling the pool where each test starts. Both are inert on SQLite. |
| `TestDatabase`, `TestDatabaseMiddleware`, `TestDatabaseExtension`, `TestDatabaseTestStarted` | The root suite's one database per test, its preparation on connect, and the extension that publishes its URL and tells it where each test starts (above). |
| `RecordingMiddleware` (with `RecordingDriver`, `RecordingConnection`, `RecordingStatement`) | Records every statement's SQL, so a test can assert what a read costs. `withoutRecording()` suspends it. |
| `SqlNormalizer` | Renumbers Doctrine's minted aliases (`s0_` → `a0_`, `t0` → `p0`, result aliases by stem), so a pinned statement survives a Doctrine release that renumbers them. |
| `DriverValues` | Canonicalises driver values: MySQL and PostgreSQL return `SUM` and `AVG` as decimal strings where SQLite returns numbers. |
| `FailingWrite` | Makes the (n+1)-th delete from an entity's table fail on every platform, through a counter and a trigger, so a test can watch a transaction roll back. |

Each Doctrine suite's `tests/Fixtures/Doctrine/EntityManagerFactory` is a thin shim over `TestEntityManagers`: its entity path, its custom types and a `seed()` helper. Two carry more:

- **schemas** adds `createForSqlite()`, the SQLite side of a test comparing platforms, and `createForPostgres()`, a manager over a PostgreSQL connection that never opens, for shape checks that only read the platform.
- **tables** passes a configuration callback that turns on an in-memory second-level cache, which caches only entities marked `#[ORM\Cache]`.

## How does a server run give every test an empty, isolated database?

In-memory SQLite gives each manager a fresh database for free. On a server, `DatabasePool` reproduces that:

- The k-th manager a test builds gets a database of its own, named `<base>[_<run>]_<package>_<TEST_TOKEN or 0>_<k>`, where `<base>` is the database the URL names and `_<run>` appears when `SYLAMENT_TEST_RUN` names a run.
- The first time a name is used, the pool drops and creates it and builds the schema. Later, it wipes it before the next test takes it.
- When a test starts, the pool closes every connection it handed the previous test, which rolls back anything left open, and resets the slot counter.

The pool runs all of this on connections of its own, so none of it reaches a test's connection or its recorder:

- One connection to the server carries `DROP DATABASE` and `CREATE DATABASE` (on PostgreSQL through the `postgres` database, with `WITH (FORCE)`).
- One housekeeping connection per database carries the schema build and every wipe, and holds the session settings: on MySQL `foreign_key_checks = 0`, `information_schema_stats_expiry = 0` and 10-second lock timeouts; on PostgreSQL `lock_timeout = '10s'`.
- A test's own connection carries only the recorder, if the test gave one.

The root suite's database is one more name, `<base>[_<run>]_suite_<TEST_TOKEN or 0>`, built the same way on its first use in a process and wiped before every later test.

The housekeeping connections stay open for the life of the process, one per pool slot. That is why a per-package server run holds many connections at once, why those runs are serial, and why `TEST_TOKEN` and `SYLAMENT_TEST_RUN` keep two processes' databases apart without keeping them inside the server's connection limit.

## How is a database wiped?

- **PostgreSQL:** one `TRUNCATE … RESTART IDENTITY CASCADE` over every table.
- **MySQL:** truncating every table was too slow per wipe, so the pool asks which tables hold rows and `DELETE`s only those. It then resets `AUTO_INCREMENT` to 1 only on the tables whose counter moved.

Both run under the bounded lock timeouts above, so a wipe that meets a lock left by a test fails loudly instead of hanging the run.

## Why does SQLite enforce foreign keys?

MySQL and PostgreSQL refuse a row whose foreign key points nowhere. SQLite accepts it unless foreign keys are switched on. Without the middleware, the default SQLite run would pass tests that fail on both servers, for example a delete by key that orphans a child row.

## Why is a per-platform expectation written out literally?

A helper that computes the expected value for each platform tends to mirror the product's own logic, so it agrees with the product whether the product is right or not. A literal `match (TestPlatform::current())` arm states the fact that was observed on each platform. The facts already pinned this way are recorded in the trail:

- a searched boolean prints `true` on PostgreSQL and `1` elsewhere: `docs/progress/schemas/search-constraint.md`, "A field that holds no character data is cast to text on PostgreSQL";
- the delete by key under enforced foreign keys: `docs/progress/actions/delete-action.md`, "The by-key arm is a DQL delete over the plucked keys";
- the key shapes and the property test: `docs/progress/schemas/record-key.md`, "The shapes the fixtures pin".

A skipped test states its platform fact in the skip message, so the skip says why the premise cannot exist there rather than just that it was turned off.

## The per-package suites

What follows describes the suites under `packages/<pkg>/tests/`, each on its own kernel; the root suite's counterparts are in the sections above.

### Why does a recorder never see a wipe, a schema build or a `PRAGMA`?

- On a server, wipes and schema builds run on the housekeeping connection, which has no recorder.
- On SQLite, `TestEntityManagers` builds the schema inside `withoutRecording()`.
- SQLite's `PRAGMA foreign_keys=ON` comes from DBAL's `EnableForeignKeys` middleware. The middleware list is `[EnableForeignKeys, recorder]`, and DBAL wraps the driver in list order, so the recorder is the outer layer. The `PRAGMA` runs on the inner connection during connect, below the recorder.

Keep that order if the list changes: putting the recorder first would record the `PRAGMA` on every SQLite connection.

### Why are support's Twig components render-tested elsewhere?

Support's fixture kernel registers FrameworkBundle, TwigBundle, UXIconsBundle and FlysystemBundle, but no TwigComponentBundle or StimulusBundle. So support's suite cannot render a `<twig:SylamentSupport:…>` tag. The five packages whose kernels do register TwigComponentBundle (`actions`, `forms`, `panels`, `schemas`, `tables`) render them through their own templates. This is deliberate, and `packages/forms/tests/Integration/Components/InputWrapperRenderTest.php` notes it. Revisit it only if support grows its own Twig-component test kernel.

Coverage follows whichever package first needed a component, so no single suite has it all. Derive the current map rather than trusting a written list:

```bash
for c in packages/support/templates/components/*/; do
  n=$(basename "$c")
  echo "$n: $(grep -rlE "SylamentSupport:${n}([ />]|\$)" packages/*/templates packages/*/tests \
    | sed -E 's#packages/([a-z]+)/(templates|tests)/.*#\1:\2#' | sort -u | tr '\n' ' ')"
done
```

A hit under a package's `templates/` means that package's suite renders the component. So does every suite that renders that package's templates in turn: `forms` renders schemas' `Tabs` partial, and `panels` renders tables' `Table` partial and forms' controls.

`InputWrapper` is the case to remember: forms' text-like controls and tables' `TextInputColumn` and `SelectColumn` cells both render it directly, so a change to it runs against both suites.

### Why does a test that asserts a toast run as a live request?

A notification is a session flash: `Notification::send()` reads the session off the `RequestStack`, and an in-process call on a host object has no request carrying one, so `callMountedAction()` throws `SessionNotFoundException` before it can toast. A test that asserts a toast, or anything the toast's re-render paints, therefore drives the host through `InteractsWithLiveComponents` (`createLiveComponent()` and `call()`), as `packages/actions/tests/Integration/CreateActionTest.php` does. A case that only checks state stays in-process, which is cheaper. The live cases disable the kernel reboot between requests, because the fixture database lives in memory on a container service and a reboot would hand the next request a freshly migrated, empty one.

### Which fixtures carry a composite identifier?

Every package that keys a record exercises both composite flavours in a test: two scalar fields, and an association plus a scalar. `CompositePair` (two scalar fields) sits in schemas', forms' and tables' `tests/Fixtures/Doctrine/Entity/`, and `Membership` (an association plus a scalar) in actions'. A new by-key site is tested over both.

Panels has a family of its own: `Catalogue`, the single-identifier parent and owner; `Stamp`, that parent association plus a converting `string` code; `Colophon`, one identifier association alone — a single derived identity, the flavour an `isIdentifierComposite` check misses; `Imprint`, two strings seeded with `.`, `/`, a space, `~`, `=` and non-ASCII; and `Edition`, an `id` plus a `locale`. Their resources mount on a non-default `key` panel of their own (`tests/Fixtures/KeyPanelProvider.php`), because the navigation and global-search suites' enumerating assertions name the `admin` and `nest` panels, and a second default panel would reorder the one they resolve.

The codec's own shape battery, with a fixture per supported identifier shape, is described in [record-key.md](../progress/schemas/record-key.md#the-shapes-the-fixtures-pin).
