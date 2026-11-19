# Test infrastructure

The reasoning behind the test rules in `.claude/rules/test-infrastructure.md` and `.claude/rules/test-database.md`; the rules for writing a test, `.claude/rules/tests.md`, `.claude/rules/test-renders.md` and `.claude/rules/test-scope.md`, are explained in [testing.md](testing.md). `test-database.md` holds the lines that govern only the Doctrine side of the fixture world, its entities, its Doctrine fixtures and the rig, so they load with those files rather than with every fixture. The rules say what to do; this file says why, and how the machinery underneath works. The commands for running the suite on a server are in the `server-gates` skill.

One suite runs, the root suite under `tests/`, one kernel and one base class for every package; the sections below describe it and the Doctrine rig it builds on.

## Why does the root suite have one kernel and one base class?

A fixture kernel per package lets the kernels disagree: different default disks, a session in some and not others, asset mapper in one, icon directories whose same-named icons differ, live routes prefixed in one kernel only. A test passing on its own package's kernel says nothing about the others. The root suite resolves each conflict once, in `Sylament\Tests\Fixtures\Kernel`, and every test runs against that one world:

- a `mock_file` session, because a toast is a session flash;
- asset mapper with an importmap declaring the `app` entrypoint the panels' document layout imports, plus `@TwigBundle/Resources/config/importmap.php`, because TwigBundle registers `importmap()` behind a `willBeAvailable()` check that refuses `symfony/asset-mapper` as a dev-only package beside a production `symfony/twig-bundle`, which is this monorepo's shape and no application's;
- one icon directory with a subfolder per area, since UX Icons maps the `<area>:` prefix of `<area>:<name>` to that subfolder;
- one Twig namespace, `@Fixtures`, with a folder per area;
- two local disks, `primary.storage` (public, with a `public_url`, so it can mint a URL) and `secondary.storage` (the no-URL and foreign-disk arms), and `primary.storage` as the default disk;
- every bundle, Sylament's nine included, in every environment, so no test depends on a bundle being absent.

`Sylament\Tests\TestCase` extends `KernelTestCase` and uses UX's `InteractsWithTwigComponents`, tables' `InteractsWithLiveComponents`, the shared `InteractsWithDatabase` and `InteractsWithRendering`, and each area's helpers (`ActionsHelpers`, `FormsHelpers`, `NotificationsHelpers`, `PanelsHelpers`, `SchemasHelpers`, `TablesHelpers`). Why one base class serves pure tests and kernel tests, and why `tests/Pest.php` boots the kernel before every package test, is in [testing.md](testing.md#why-is-there-one-kind-of-test-file).

## How is the kernel configured?

`MicroKernelTrait` already imports `packages/*.php`, `packages/<env>/*.php`, `services.php` and `routes/*.php` from the kernel's config directory. The kernel's `getProjectDir()` answers `tests/Fixtures`, where the default would walk up to the repository's `composer.json`, and it keeps the trait's `configureContainer()`, so the configuration is Symfony's own file layout under `tests/Fixtures/config/`, one file per extension. The whole fixture tree takes a Symfony app's layout for the same reason: the classes sit in `src/`, which a second autoload-dev entry maps to `Sylament\Tests\Fixtures\` inside the `tests/` one, so the namespace carries no `src` segment, and `templates/`, `assets/` and `importmap.php` sit where Symfony's defaults resolve against the project directory, so ux-icons finds `assets/icons/` and asset mapper `importmap.php` without a path in config. The class itself keeps what a file cannot: the bundle list, the writable paths, the route order, and the component-list pass, and the compile-only read behind it (below).

A file a test uploads sits in `assets/uploads/` and reaches the field as a test-mode `UploadedFile`, which skips the `is_uploaded_file()` check a real request needs. The temporary store reads it through a stream, never through `move()`, the one call that would take it out of the tree, so the source file survives every run and no test copies it first.

An environment changes one configuration value every other test would otherwise observe, in its own `packages/<env>/` folder. `test_mode_off` turns `framework.test` off, so a test can read what the container holds outside test mode, such as the test-only services support's `RemoveTestOnlyServicesPass` removes; no test boots it, since `KernelTestCase::getContainer()` needs the `test.service_container` that mode lacks, so it is read compile-only. `registered_colors` registers palettes under the `sylament.colors` node, so a test can boot it and read what the node registers, while on the default kernel `sylamentStyles()` stays empty and no page carries a palette no test asked for. `registered_icons` overrides `modal.close-button`, forms' `components.text-input.actions.show-password` and panels' `pages.dashboard.navigation-item` under the `sylament.icons` node, so a test can boot it and read that a configured dashed alias reaches a render, a support template's, a container-built forms action's and the dashboard's navigation item alike, while on the default kernel every modal, revealable password field and dashboard item keeps its shipped icon.

`services.php` registers `Sylament\Tests\Fixtures\` by glob with autowire and autoconfigure, so a class placed in an area's fixture folder is its own registration. The glob covers only `src/`, so the configuration, templates and assets beside it need no exclude; it excludes the kernel, an area's `Selectors.php`, which holds only constants and static helpers, an area's `Record/` folder of plain records a test builds with `new`, an area's `Doctrine/` folder of DBAL types, filters and entity listeners, which Doctrine instantiates by class name, with the values they convert, and entity folders, which are mapped by Doctrine rather than registered. It needs no exclude for a trait, an interface, an abstract class or an enum: Symfony's loader skips a trait and registers an interface or an abstract class as an abstract definition tagged `container.excluded`, and autoconfiguration tags an enum `container.excluded` too, so none can become a service and compilation removes each. A host a test builds with `new` (`RecordMappingHost`) stays in the glob: it takes no constructor arguments, its one dependency arriving through a `#[Required]` setter (the validator), so it registers as an ordinary definition, and with nothing referring to it the compiler removes it, while an exclude is for a class that could only register as a broken definition, such as a record with scalar constructor arguments.

The fixtures stay private, and `test.service_container` reaches a private service only if it survives compilation: `RemoveUnusedDefinitionsPass` removes a private definition no other service refers to, so the test container reaches `HookLog`, which the hosts that record into it inject, but a fetch of an unreferenced fixture fails as "removed or inlined", and a test builds such a fixture with `new` instead.

A fixture sits in the lowest area that needs it, so the fixture world grows upward with the packages and a lower area never waits on a higher one. It never sits below the package of a class it builds on, because a support fixture that builds a table would make support's area depend on tables, the reverse of the packages' own graph. Schemas, forms and actions require one another, so no order between them is real: a schemas host whose state lives in a forms `TextInput` (`CodeForm`) builds on a package schemas itself requires, and it stays in schemas' area, the lowest whose tests need it, rather than in forms', where a fixture the forms tests own would carry schemas' tests.

The routes are imported in a fixed order: the live routes under `/_components`, then the panel route loader, then every other `routes/*.php`. UX's live route is `/{_live_component}/{_live_action}`, which would match any two-segment fixture path or panel URL if it were mounted at the root; the recipe's prefix keeps it out of the way. A glob import sorts files by name, so the kernel imports `routes/live_component.php` itself and excludes it from the glob that follows.

In debug mode FrameworkBundle dumps a `reference.php` into the kernel's config directory on every container build. Here that directory is source, shared by every run and every environment, and the dumped content differs per environment, so two environments would keep rewriting it and invalidating each other's container. The kernel drops the `.kernel.config_dir` parameter, which only that dump reads, and the file is never written.

The `@Fixtures` folder exists even before an area adds a template, because Twig's `FilesystemLoader::addPath()` throws on a missing directory the moment the Twig environment is built.

Booting the kernel inside a test needs no error-handler bootstrap: FrameworkBundle registers Symfony's error handler without replacing one, and finding PHPUnit's in place, it restores both handlers, so PHPUnit sees nothing left behind.

## Why does every run write under its own directory?

The root suite runs in one process: a run owns one directory and one database, so no fixture, path or database has to be shared safely between processes, and no failure depends on how a run split the files. `TestDatabaseExtension` refuses `--parallel` before it does anything else, detected by Pest's own `Parallel::isEnabled()` and, in a worker, ParaTest's `PARATEST=1`, so the flag fails naming its cause rather than on the event facade Pest's coordinator seals before it bootstraps extensions.

`WorkerDirectory::path()` answers `var/tests/<run>`, where `<run>` is the run `SYLAMENT_TEST_RUN` names, or `default` when it names none. Everything the suite writes lives under it: the compiled container and the router's cache files (the build directory defaults to the cache directory), the session files, the SQLite files under `database/`, and the disks under `storage/`. The cache directory is `cache/<env>/<platform>` (see the database section below). The kernel exposes the run's directory as the `sylament_tests.worker_dir` parameter for configuration files. Two processes compiling one container into one directory corrupt each other's fixed-name files, which is why nothing writes to `sys_get_temp_dir()` or a fixed path.

The run that names none still takes a segment of its own, `default`, so every run's directory sits beside every other's and none sits inside another. Were the unnamed run to write into `var/tests/` itself, a run named `cache`, `database`, `storage` or `log` would land in the unnamed run's directory of that name, where the teardown that removes `storage/` after every test, or a cleared `cache/`, would reach the other run's files. `WorkerDirectoryTest` pins the layout.

The `logger` service is Symfony's `Logger` at `emergency`, set in `config/services.php`. FrameworkBundle's fallback prints every error a request logs to stderr, and a tester that lets the kernel catch exceptions logs each one it rethrows, so the default would put a critical line on stderr for every rethrown exception. Nothing reads a log file, so none is written.

Two runs in one checkout, which agents and workflows share by default, would compile into, copy over and wipe the same directories and databases, and each would fail the other's tests at random, with failures that look like product bugs. A second run therefore sets `SYLAMENT_TEST_RUN` (1 to 16 characters of `a-z`, `0-9` and `_`, so it is safe in a path and a database name), which names its directory and its server database, `<base>_<run>`. A run that forgets it is stopped rather than trusted: in every process that runs tests, `TestDatabaseExtension` takes an exclusive, non-blocking `flock` on the run directory's `.lock` and holds it until the process ends, and a process that finds it held prints the message naming the variable and exits before its first test. It exits instead of throwing because PHPUnit turns an exception thrown while an extension bootstraps into a warning and runs every test anyway.

Debug stays on: a run recompiles its container when a fixture changes, and Symfony checks freshness once per process. Two edits escape that check. ux-icons keeps each icon it renders in a cache pool under the kernel's cache directory, keyed by the icon's name, so an edited fixture SVG renders its old markup until each run's `cache/` under `var/tests/` is removed; a new SVG has no entry yet and is read on first use. The other is an edit inside `Kernel::build()` or a compiler pass (below).

## Why does `tearDown()` reset every static instance?

A pure test still reaches the static instances: `make()` falls back to `new` when no kernel is booted, and `configureUsing()` resolves a `ComponentManager` of its own. So `TestCase::tearDown()` calls `unsetInstance()` on every loaded class implementing `StaticInstance` after every test, booted or not. The list comes from `get_declared_classes()`, recomputed only when the count of declared classes changes: a class never loaded holds no instance, so the loaded classes are the whole list, and no kernel has to boot to name them. A new static instance is therefore reset without anyone editing a list. `TestCaseTest` pins it: a `configureUsing()` closure registered in one case is gone in the next. Its second case is meaningful only when it runs after the first in the same process, so a filtered run of that case alone passes vacuously. The same teardown removes the run's `storage/`, so no upload survives into the next test.

## How does a test get every component that offers something?

A case that must run over every component offering a behaviour takes its dataset from `ComponentRegistry` (why a list is derived rather than typed is in [testing.md](testing.md#why-is-a-list-of-components-derived)). The components are the services tagged `sylament.component`, which support's bundle adds by autoconfiguration, and a compiled container keeps no tags. So `ComponentListPass`, which the kernel's `build()` adds, exports their classes as the `sylament_tests.components` parameter: sorted, without duplicates, and without `Sylament\Tests\` classes, since a fixture subclass of a component is no component a user gets.

Pest resolves a dataset while it collects the suite, before any test or `$this` exists, so the registry cannot ask a test for the container. It reads the parameter once per process and memoises it, from a kernel of its own that compiles (and on a cold cache warms) its container without booting it: `Kernel::getCompiledContainer()` runs the kernel's `initializeBundles()` and `initializeContainer()` and stops there. Booting would run every bundle's `boot()`, and support's binds the static instances, which the first test would then start with. Compiling alone binds none, and costs one container compile, cache warmers included, on a cold cache. What does outlive the read is harmless, and includes Symfony's per-process note that the cached container is fresh (`AbstractKernel::$freshCache`) and the registry's own memo. A process that collects no derived dataset never reads the parameter.

A bundle's test that it registers every component as a service is the one reader of a component list from disk: it walks its package's `src/Components/` and asks the container for each class it finds. The registry lists only what the bundle tagged, so a level the bundle's service glob leaves out would be missing from both the list and the container, and a test reading the registry would pass; `make()` would fall back to `new` without a word.

The datasets are keyed by short class name, so a test reads as "with dataset \"TextInput\"". Two components sharing a short name in one result throw rather than one silently replacing the other; `Sylament\Schemas\Components\Group` and `Sylament\Tables\Grouping\Group` already share one.

`tests/Infrastructure/ComponentRegistryTest.php` pins the residue: after the list is read, no static instance is bound. The case resets the registry's memo through reflection before it reads, because once any earlier read has memoised the list the case would read nothing, and an earlier test's `tearDown()` has already unset every static instance, so it would pass whatever the registry did. The same file pins `componentsUsing()` through a trait that `Select` composes by way of another trait and `TextInput` does not use.

Symfony checks the kernel class and each compiler pass for changes by their signatures, not their method bodies, so an edit inside `Kernel::build()` or `ComponentListPass::process()` reaches a run's cached container only after that run's `cache/` under `var/tests/` is removed.

## Why are there no free helper functions?

Every root-suite test file shares its namespace with the others (the global one, or the scans' `Sylament\Tests\Architecture`), so two test files that each declare a helper function of one name would collide. A function defined in one test file and called from another also breaks in a run of the calling file alone, which never loads the defining one. Helpers that need the container are methods of `TestCase` from traits, and pure ones are static methods, both of which PHPStan sees. The same reason puts a custom expectation in `tests/Expectations/` and a dataset several files share in `tests/Datasets/`: Pest loads both folders in every run before any test, so the run of one file finds them, and a dataset's name is global, so one file per area keeps two areas' names apart. Pest `include_once`s every file under `tests/Expectations/`, `tests/Helpers/` and `tests/Datasets/` at boot, and Composer autoloads a class again, so a class placed there is declared twice.

## Why do the test scripts turn Xdebug off?

Every `composer test:*` script that runs Pest sets `XDEBUG_MODE=off` first. A PHP build that loads Xdebug in its default `develop` mode instruments every function call, and Composer hands its scripts the PHP configuration it was started with, so on such a machine each live request a test makes costs about 73 ms against 8.5 ms without it, and the root suite runs about five times slower. A coverage run needs no Xdebug either: pcov serves `--coverage-*`. CI loads no Xdebug at all (`coverage: none`), so the variable changes nothing there. A run through `vendor/bin/pest` directly, as an IDE or a step debugger starts it, keeps whatever mode the machine sets.

## What does PHPStan analyse in the root suite?

Everything under `tests/` except every `*Test.php` file in it, `tests/Pest.php` and `tests/Expectations/`. PHPStan types a Pest closure's `$this` as `Pest\PendingCalls\TestCall`, so `$this::getContainer()` fails at any level, and custom expectations are reached through `__call`. The kernel, its configuration, the infrastructure and the fixtures are ordinary PHP and are held to level max like shipped code.

## How does the root suite give every test one database?

Every manager the kernel's `doctrine` registry holds, in the test itself or in any request it sends, works on one database, and that database starts each test empty, identity counters included, on SQLite, MySQL and PostgreSQL. The managers come from DoctrineBundle and a request runs on a container of its own, so the database cannot belong to a manager or a container: a request has to see what the test seeded.

### Configuration

`tests/Fixtures/config/packages/doctrine.php` configures DoctrineBundle:

- One `default` connection, whose URL the container reads at runtime from `SYLAMENT_TEST_CONNECTION_URL`. `TestDatabaseExtension` publishes it before any test is collected: on SQLite the run's `database/test.sqlite`, on a server the URL `SYLAMENT_TEST_DATABASE_URL` names with its database renamed to the run's, `<base>_<run>` (below). A value read through `getenv()` while the container compiles is not a tracked resource, so a compiled container would keep a URL after the environment changed; the kernel's cache directory is keyed by platform as well, because `EnableForeignKeys` is registered only on SQLite.
- The ORM in `entity_managers:` form, so an area can add a named manager without restructuring the file. It maps one folder, `tests/Fixtures/src/Entity/`, as one `attribute` mapping under `Sylament\Tests\Fixtures\Entity`.
- One set of entities for every area. Each exists for a shape a test needs: a single identifier (`Product`), each composite flavour (`OrderLine`, `InvoiceLine`), a parent (`Invoice`), lifecycle hooks (`HookedArticle`), the rig's own probe (`Tally`). Filament's suite instead shares a few general models (`Post`, `User`, `Team`), which grow a column for every feature until a test that lists fields or counts statements breaks when another package's test adds one; a purpose-built entity is changed by nobody, and a test that needs a different shape adds an entity. An entity in an area's folder would look owned by that area while higher areas read it; one shared folder says that a change to it reaches every test that reads it.
- One metadata cache for the whole process, `TestDatabase::metadataCache()`. DoctrineBundle's default pool lives in each container, so every boot would read the attributes again; `cache.system` lives on disk and would outlast an entity edit. The service is not shared, because `Container::reset()`, which `KernelTestCase` runs at every kernel shutdown, resets every shared service it holds, and that would empty the cache. It deep-clones what it stores and what it hands out (`ArrayAdapter`'s default), so no two managers share a `ClassMetadata` object. Query and result caches, filters, hints and listeners stay per container, because tests change them. Metadata is cached after the `loadClassMetadata` listeners ran, though, so a test that varies a class's metadata through such a listener needs a manager with a metadata cache of its own.
- The second-level cache, on with an in-memory region cache per container; it caches only entities marked `#[ORM\Cache]`.
- DBAL's logging off and its profiling on: DoctrineBundle then puts its debug middleware on the connection, which collects every statement into `doctrine.debug_data_holder`, and `statementsRun()` reads that (see [the middleware order](#the-middleware-order)). The holder lives in each container and is reset with its services, which Symfony does at every kernel shutdown and as each request after a kernel's first starts, so after a request it holds at most that request's statements, and `seed()` empties it so only the statements under test count.
- Custom DBAL types go under `dbal.types` and filters under the manager's `filters`, in the area's own config file, with their classes in the area's `Doctrine/`: the area owns both, so it adds one without editing the shared `doctrine.php`.

### Lifecycle

The client reboots the kernel between requests, Symfony's default, so each request after the first builds a new container and a new manager, as production does. If building a manager prepared the database, the second request would erase the test's seed. The state therefore lives in the process, in `TestDatabase`, never in a container service:

1. At each test's start, the extension's subscriber calls `TestDatabase::testStarted()`, which closes the connections the kernel opened in the previous test and marks the database stale. DoctrineBundle already closes every connection when its kernel shuts down, which `KernelTestCase` does after every test; this closes one reopened afterwards.
2. `TestDatabaseMiddleware` wraps the driver, and its `connect()` has `TestDatabase` prepare the database before connecting, once per test. On SQLite that deletes the run's `test.sqlite` with its `-journal`, `-wal` and `-shm` sidecars, since a stale one would replay onto the fresh copy, and copies the template over it. On a server `ServerDatabase` wipes the run's database, after running the clean-ups queued for it. DBAL connects lazily, so building a manager prepares nothing, and a test that never connects pays nothing.
3. The schema is built once per process, at the first preparation: from every manager the registry holds, through a manager built over that manager's configuration and event manager on a connection of `TestDatabase`'s or `ServerDatabase`'s own. Neither `statementsRun()` nor the connecting connection sees it, and preparing never reaches the connection being opened; a nested preparation throws rather than recursing. On SQLite the template goes to the run's `database/template.sqlite`, deleted and rebuilt by every process, never reused from an earlier run; on a server `ServerDatabase` drops and creates the run's database and builds it there.
4. `entityManager()` fetches from the current container on every call, so after a request it answers the rebooted kernel's manager.

A few premises live in one kernel's memory across requests: a second-level cache region an earlier request warmed, which a delete must evict, or a `kernel.reset` service that must clear what one request left before the next. A rebooted kernel starts with an empty region and fresh services, so it cannot fail such a case; those cases alone hand `createLiveComponent()` a client with `disableReboot()`, and every other case keeps the reboot, so no case leans on memory production never shares between requests.

`tests/Infrastructure/Doctrine/TestDatabaseTest.php` pins the lifecycle: a seed survives two requests through a rebooting client, and the next test starts empty with an identifier of 1. Its second case means something only after the first, in the same process. Its third crosses the boundary inside one case, calling `testStarted()` as the subscriber does, because between two real tests the kernel's shutdown already closes the connection and would hide a `testStarted()` that closed nothing; `ServerDatabaseTest` and `FailingWriteTest` cross it the same way.

### Why not roll each test back instead?

Symfony applications usually get a clean database per test, across kernel reboots, from `dama/doctrine-test-bundle`: it keeps one static connection per process, opens a transaction before each test and rolls it back afterwards, and turns the application's own transactions into savepoints. It would replace the per-test preparation above, though not the schema build, the per-run database or the URL each run publishes, which it leaves to the suite. Two guarantees of the contract break under it:

- **Fresh identity counters.** A rolled-back insert still consumes MySQL's `AUTO_INCREMENT` value and PostgreSQL's sequence value, so identifiers would restart at 1 on SQLite alone, and a test leaning on one would pass on SQLite and fail only at the server gates.
- **DDL inside a test.** `FailingWrite` creates a table and a trigger while the test runs, and MySQL commits implicitly before such a statement, which ends the wrapping transaction and leaves the test's rows to the next test.

It is also a third-party package from outside the circle the project builds on ([dependencies.md](dependencies.md#third-party-grants)), so the suite keeps its own preparation.

### Platform-pinned managers

A premise that compares platforms, such as a shape check that only PostgreSQL's guid type makes necessary, needs a manager whose platform the test names rather than the run. `InteractsWithDatabase::platformEntityManager()` builds one over the world's mapping:

- **SQLite** answers a manager on an in-memory database of its own, its schema built by `TestDatabase::buildSchema()`, the same build the world's preparation runs, and `EnableForeignKeys` added by hand, because `doctrine.php` registers it only on a SQLite run. The same manager serves a case that needs a second manager beside the world's, such as a reader rebound with `withEntityManager()` that must read nothing the world seeded: it is isolated, empty and mapped like the world, with nothing for the test to build.
- **MySQL and PostgreSQL** answer a manager over a connection that never opens: told its server version, DBAL names the platform without connecting, which is all a platform read needs, so nothing on it may run a statement.

Each clones the world's ORM configuration and shares only the world's event manager, so the mapping's listeners still run; nothing else the world could see is shared. Its connection carries neither the profiling middleware nor `TestDatabaseMiddleware`, so its statements stay out of `statementsRun()` and it never prepares the world's database. It has a metadata cache of its own, because loaded metadata can depend on the platform (the identifier strategy `AUTO` resolves to) and the shared cache would hand a manager on one platform another's. The second-level cache is off, since its regions belong to the world's container, and the repository factory is Doctrine's default, since a repository service answers for the world's manager whatever manager asked for it.

`tests/Infrastructure/Concerns/InteractsWithDatabaseTest.php` pins `statementsRun()` (what it counts and leaves out, its renumbering, its reset at `seed()`), the isolation, the separate caches and repository factory, the foreign keys and the platform reads without a connection, on all three gates.

### SQLite runs as a file

The test connection turns off `synchronous` and keeps its journal in memory, because the file lives for one test. Measured, 200 cycles of preparing, seeding one row and reading it back took 78.5 ms with SQLite's defaults and 41.3 ms with both pragmas. The middleware runs them after connecting, below the profiling middleware, so `statementsRun()` never counts them.

### The middleware order

DoctrineBundle sorts the `doctrine.middleware` services by descending priority, and DBAL wraps the driver in that order, so the highest priority is the innermost layer. `EnableForeignKeys` (SQLite only, priority 100) sits innermost, `TestDatabaseMiddleware` (50) around it, and DoctrineBundle's profiling middleware (its own priority 10) outside both. DoctrineBundle builds one instance of each per connection from the registered definition, which is therefore abstract.

What runs as a connection opens stays out of `statementsRun()` by two separate means:

- **The order hides the `PRAGMA`s.** `EnableForeignKeys` runs `PRAGMA foreign_keys=ON` while it connects, and `TestDatabaseMiddleware` its two durability pragmas on the connection it gets back, both below the profiling layer, which wraps the connection only once they have run. Keep both above priority 10: one placed outside the profiling layer would have its `PRAGMA`s counted on every SQLite connection.
- **Connections of their own hide the preparation.** `TestDatabaseMiddleware` prepares before it connects, and nothing preparing runs reaches the kernel's connection: the schema build and every wipe run on connections `TestDatabase` and `ServerDatabase` open through `DriverManager` themselves, which carry no middleware, and the per-test SQLite preparation is a file copy, which runs no statement.

`statementsRun()` checks that the default connection carries the profiling middleware before it reads the holder, because an unprofiled connection collects nothing, and a test asserting that no statement ran would then pass whatever the code did.

A recorder of the suite's own is not needed: DoctrineBundle's profiling already collects each statement's SQL, prepared statements included and recorded when they execute, and marks a transaction as `"START TRANSACTION"`, `"COMMIT"` and `"ROLLBACK"`, quotes included.

## Why does the Doctrine rig live in the root suite's infrastructure?

The Doctrine rig sits in `tests/Infrastructure/Doctrine/` and `tests/Infrastructure/PHPUnit/` (`Sylament\Tests\Infrastructure\Doctrine\`, `\PHPUnit\`), beside the root suite's own infrastructure, which builds on it. The root `composer.json` `autoload-dev` entry for `Sylament\Tests\` covers it, so it needs no autoload entry or package of its own.

It is dev-only. The root `tests/` belongs to no package, so none of it ships in a split package, and nothing under any `src/` imports it. Two consequences follow:

- PHPStan analyses it at level max like shipped code, because it sits under `tests/`, whose infrastructure it covers.
- The architecture tests that scan `packages/*/src` (`StaticAnalysisSuppressionTest`, `DbalParameterTypeDeclarationTest`, `AssociationReaderConstructionTest`) do not see it. The infrastructure is free to use what those checks forbid in shipped code.

The root suite pins the pieces at their mirrored paths (`tests/Infrastructure/Doctrine/<Class>Test.php`), against the world's database, each only for the behaviour the suite relies on.

## What is in the rig?

| Piece | Job |
|---|---|
| `TestPlatform` | Reads `SYLAMENT_TEST_DATABASE_URL`: unset or empty means SQLite, a file per run, `mysql://` or `postgresql://` (or `pgsql://`) names a server, any other scheme throws. |
| `ServerDatabase` | The run's one database on a server, `<base>_<run>` (below): `prepare()` creates it at its first use and wipes it before every later test. `cleanUpBeforeNextWipe()` queues statements to drop a test's own trigger or helper table. |
| `TestDatabase`, `TestDatabaseMiddleware`, `TestDatabaseExtension`, `TestDatabaseTestStarted` | The root suite's one database per test, its preparation on connect, and the extension that refuses `--parallel`, publishes the database's URL, claims the run's directory and tells the database where each test starts (above). |
| `SqlNormalizer` | Renumbers Doctrine's minted aliases (`s0_` → `a0_`, `t0` → `p0`, result aliases by stem), so a pinned statement survives a Doctrine release that renumbers them. |
| `DriverValues` | Canonicalises driver values: MySQL and PostgreSQL return `SUM` and `AVG` as decimal strings where SQLite returns numbers. |
| `FailingWrite` | Makes the (n+1)-th row deleted from an entity's table or a many-to-many's join table, or updated in an entity's table, fail on every platform, through a counter and a trigger, so a test can watch a transaction roll back; a join table has no entity class to name, so it is reached through its owner and association, and an update counts in a table of its own. |

## How does a server run give every test an empty, isolated database?

`ServerDatabase` gives a server run what the SQLite template gives a SQLite run:

- The run has one database, named `<base>_<run>`, where `<base>` is the database the URL names and `<run>` is `SYLAMENT_TEST_RUN`, or `default`. A URL that names no database is refused, since there is no name to derive the run's from.
- At its first use in a process, it drops and creates the database and builds the schema. Before every later test, it runs the clean-ups queued since the last wipe, then wipes it.

It runs all of this on connections of its own, so none of it reaches the kernel's connection or `statementsRun()`:

- One connection to the server carries `DROP DATABASE` and `CREATE DATABASE` (on PostgreSQL through the `postgres` database, with `WITH (FORCE)`).
- One housekeeping connection to the run's database carries the schema build and every wipe, and holds the session settings: on MySQL `foreign_key_checks = 0`, `information_schema_stats_expiry = 0` and 10-second lock timeouts; on PostgreSQL `lock_timeout = '10s'`.

The housekeeping connection stays open for the life of the process, beside the connections the tests hold. `SYLAMENT_TEST_RUN` keeps two runs' databases apart, not their connections: every concurrent run draws on the server's one connection limit, which is why the `server-gates` skill runs one server suite at a time.

`tests/Infrastructure/Doctrine/ServerDatabaseTest.php` pins the wipe (every table emptied and every identity counter restarted, including one a rolled-back insert moved while its table stayed empty) and the refusal of a URL that names no database.

## How is a database wiped?

- **PostgreSQL:** one `TRUNCATE … RESTART IDENTITY CASCADE` over every table.
- **MySQL:** truncating every table was too slow per wipe, so `ServerDatabase` asks which tables hold rows and `DELETE`s only those. It then resets `AUTO_INCREMENT` to 1 only on the tables whose counter moved.

Both run under the bounded lock timeouts above, so a wipe that meets a lock left by a test fails loudly instead of hanging the run.

## Why does SQLite enforce foreign keys?

MySQL and PostgreSQL refuse a row whose foreign key points nowhere. SQLite accepts it unless foreign keys are switched on. Without the middleware, the default SQLite run would pass tests that fail on both servers, for example a delete by key that orphans a child row.

## Why is a per-platform expectation written out literally?

A helper that computes the expected value for each platform tends to mirror the product's own logic, so it agrees with the product whether the product is right or not. A literal `match (TestPlatform::current())` arm states the fact that was observed on each platform. The facts already pinned this way are recorded in the trail:

- a searched boolean prints `true` on PostgreSQL and `1` elsewhere: `docs/progress/schemas/search-constraint.md`, "A field that holds no character data is cast to text on PostgreSQL";
- the delete by key under enforced foreign keys: `docs/progress/actions/delete-action.md`, "The by-key arm is a DQL delete over the plucked keys";
- the key shapes and the property test: `docs/progress/schemas/record-key.md`, "The shapes the fixtures pin".

A skipped test states its platform fact in the skip message, so the skip says why the premise cannot exist there rather than just that it was turned off.

## Which tests render a support Twig component?

The root suite renders every `<twig:SylamentSupport:…>` tag in `tests/Packages/Support/Twig/Components/`. Each package's tests under `tests/Packages/` render the support components its own templates use, so a change to a support component also runs against those packages' tests.

Which packages reach a component follows whichever package needed it. Derive the current map rather than trusting a written list:

```bash
for c in packages/support/templates/components/*/; do
  n=$(basename "$c")
  echo "$n: $(grep -rlE "SylamentSupport:${n}([^A-Za-z0-9]|\$)" packages/*/templates tests/Packages \
    | sed -E 's#packages/([a-z]+)/templates/.*#\1:templates#; s#tests/Packages/([A-Za-z]+)/.*#\1:tests#' | sort -u | tr '\n' ' ')"
done
```

A hit under a package's `templates/` means that package's tests render the component. So do the tests of every package that renders that package's templates in turn: `panels` renders tables' `Table` partial and forms' controls.

`InputWrapper` is the case to remember: tables' `TextInputColumn` and `SelectColumn` cells render it directly, and `panels` renders the forms controls that compose it, so a change to it runs against the forms, tables and panels tests alike.

## Why does a test that asserts a toast run as a live request?

A notification is a session flash: `Notification::send()` reads the session off the `RequestStack`, and an in-process call on a host object has no request carrying one, so `callMountedAction()` throws `SessionNotFoundException` before it can toast. A test that asserts a toast, or anything the toast's re-render paints, therefore drives the host through `InteractsWithLiveComponents` (`createLiveComponent()` and `call()`). A case that only checks state stays in-process, which is cheaper.

## Which fixtures carry a composite identifier?

Every package that keys a record exercises both composite flavours in a test: two scalar fields, and an association plus a scalar. `OrderLine` (two scalar fields) and `InvoiceLine` (an association plus a scalar) sit in the shared `tests/Fixtures/src/Entity/`. A new by-key site is tested over both.

Panels' composite cases run over the `admin` panel's `OrderLineResource` and `InvoiceLineResource` where those serve, and otherwise over resources of a non-default panel of their own in `tests/Fixtures/src/Panels/`, because `PanelRegistry::getDefault()` answers the first default panel registered, so a second `default()` would change the panel every page built outside a route answers for.

The codec's own shape battery, with a fixture per supported identifier shape, is described in [record-key.md](../progress/schemas/record-key.md#the-shapes-the-fixtures-pin).
