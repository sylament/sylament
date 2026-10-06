# Test infrastructure

The reasoning behind the test rules in `.claude/rules/tests.md` and `.claude/rules/doctrine-tests.md`. The rules say what to do; this file says why, and how the machinery underneath works. The commands for running the suites on a server are in the `server-gates` skill.

## Why does the shared infrastructure live in support's test tree?

Five suites run against Doctrine (`actions`, `forms`, `panels`, `schemas`, `tables`) and share one rig. It sits in `packages/support/tests/Doctrine/` and `packages/support/tests/PHPUnit/`, beside `Sylament\Support\Tests\TestCase`, which every package's own `TestCase` already extends. The root `composer.json` `autoload-dev` entry for `Sylament\Support\Tests\` covers it, so no new autoload entry or package was needed.

It is dev-only. Support's `.gitattributes` export-ignores `/tests`, so none of it ships, and nothing under any `src/` imports it. Two consequences follow:

- `phpstan.neon.dist` names the two directories explicitly, so they are analysed at level max like shipped code. No other test file is analysed.
- The architecture tests that scan `packages/*/src` (`StaticAnalysisSuppressionTest`, `DbalParameterTypeDeclarationTest`, `AssociationReaderConstructionTest`) do not see it. The infrastructure is free to use what those checks forbid in shipped code.

Support's own suite tests the rig: its tests sit in `tests/Unit/Doctrine/` and its fixtures in `tests/Fixtures/Doctrine/`, mirroring the infrastructure rather than `src/`. Support's fixture kernel boots no Doctrine, so it excludes `Fixtures/Doctrine` from its service glob. Support's `phpunit.dist.xml` registers the pool extension too, because those tests exercise the pool.

## What is in the rig?

| Piece | Job |
|---|---|
| `TestPlatform` | Reads `SYLAMENT_TEST_DATABASE_URL`: unset or empty means in-memory SQLite, `mysql://` or `postgresql://` (or `pgsql://`) names a server, any other scheme throws. |
| `TestEntityManagers` | Builds a package's manager. On SQLite it creates the schema in memory; on a server it asks the pool. `createSqlite()` always answers SQLite. It registers custom types once per process and takes an optional configuration callback. |
| `DatabasePool` | Hands a server test the databases SQLite would give it (below). `cleanUpBeforeNextWipe()` queues statements to drop a test's own trigger or helper table. |
| `DatabasePoolExtension`, `DatabasePoolTestStarted` | The PHPUnit extension and the subscriber it registers, telling the pool where each test starts. Both are inert on SQLite. |
| `RecordingMiddleware` (with `RecordingDriver`, `RecordingConnection`, `RecordingStatement`) | Records every statement's SQL, so a test can assert what a read costs. `withoutRecording()` suspends it. |
| `SqlNormalizer` | Renumbers Doctrine's minted aliases (`s0_` → `a0_`, `t0` → `p0`, result aliases by stem), so a pinned statement survives a Doctrine release that renumbers them. |
| `DriverValues` | Canonicalises driver values: MySQL and PostgreSQL return `SUM` and `AVG` as decimal strings where SQLite returns numbers. |
| `FailingWrite` | Makes the (n+1)-th delete from an entity's table fail on every platform, through a counter and a trigger, so a test can watch a transaction roll back. |

Each Doctrine suite's `tests/Fixtures/Doctrine/EntityManagerFactory` is a thin shim over `TestEntityManagers`: its entity path, its custom types and a `seed()` helper. Two carry more:

- **schemas** adds `createForSqlite()`, the SQLite side of a test comparing platforms, and `createForPostgres()`, a manager over a PostgreSQL connection that never opens, for shape checks that only read the platform.
- **tables** passes a configuration callback that turns on an in-memory second-level cache, which caches only entities marked `#[ORM\Cache]`.

## How does a server run give every test an empty, isolated database?

In-memory SQLite gives each manager a fresh database for free. On a server, `DatabasePool` reproduces that:

- The k-th manager a test builds gets a database of its own, named `<base>_<package>_<TEST_TOKEN or 0>_<k>`, where `<base>` is the database the URL names.
- The first time a name is used, the pool drops and creates it and builds the schema. Later, it wipes it before the next test takes it.
- When a test starts, the pool closes every connection it handed the previous test, which rolls back anything left open, and resets the slot counter.

The pool runs all of this on connections of its own, so none of it reaches a test's connection or its recorder:

- One connection to the server carries `DROP DATABASE` and `CREATE DATABASE` (on PostgreSQL through the `postgres` database, with `WITH (FORCE)`).
- One housekeeping connection per database carries the schema build and every wipe, and holds the session settings: on MySQL `foreign_key_checks = 0`, `information_schema_stats_expiry = 0` and 10-second lock timeouts; on PostgreSQL `lock_timeout = '10s'`.
- A test's own connection carries only the recorder, if the test gave one.

The housekeeping connections stay open for the life of the process, one per pool slot. That is why a server run holds many connections at once, why server runs are serial, and why `TEST_TOKEN` keeps two parallel processes' databases apart without keeping them inside the server's connection limit.

## How is a database wiped?

- **PostgreSQL:** one `TRUNCATE … RESTART IDENTITY CASCADE` over every table.
- **MySQL:** truncating every table was too slow per wipe, so the pool asks which tables hold rows and `DELETE`s only those. It then resets `AUTO_INCREMENT` to 1 only on the tables whose counter moved. The per-table timings that motivated this were measured once and are not re-run here.

Both run under the bounded lock timeouts above, so a wipe that meets a lock left by a test fails loudly instead of hanging the run.

## Why does a recorder never see a wipe, a schema build or a `PRAGMA`?

- On a server, wipes and schema builds run on the housekeeping connection, which has no recorder.
- On SQLite, `TestEntityManagers` builds the schema inside `withoutRecording()`.
- SQLite's `PRAGMA foreign_keys=ON` comes from DBAL's `EnableForeignKeys` middleware. The middleware list is `[EnableForeignKeys, recorder]`, and DBAL wraps the driver in list order, so the recorder is the outer layer. The `PRAGMA` runs on the inner connection during connect, below the recorder.

Keep that order if the list changes: putting the recorder first would record the `PRAGMA` on every SQLite connection.

## Why does SQLite enforce foreign keys?

MySQL and PostgreSQL refuse a row whose foreign key points nowhere. SQLite accepts it unless foreign keys are switched on. Without the middleware, the default SQLite run would pass tests that fail on both servers, for example a delete by key that orphans a child row.

## Why is a per-platform expectation written out literally?

A helper that computes the expected value for each platform tends to mirror the product's own logic, so it agrees with the product whether the product is right or not. A literal `match (TestPlatform::current())` arm states the fact that was observed on each platform. The facts already pinned this way are recorded in the trail:

- a searched boolean prints `true` on PostgreSQL and `1` elsewhere: `docs/progress/schemas/search-constraint.md`, "A field that holds no character data is cast to text on PostgreSQL";
- the delete by key under enforced foreign keys: `docs/progress/actions/delete-action.md`, "The by-key arm is a DQL delete over the plucked keys";
- the key shapes, the property test and the mutation gate: `docs/progress/schemas/record-key.md`, "The shapes the fixtures pin".

A skipped test states its platform fact in the skip message, so the skip says why the premise cannot exist there rather than just that it was turned off.

## Why are support's Twig components render-tested elsewhere?

Support's fixture kernel registers FrameworkBundle, TwigBundle, UXIconsBundle and FlysystemBundle, but no TwigComponentBundle or StimulusBundle. So support's suite cannot render a `<twig:SylamentSupport:…>` tag. The six packages whose kernels do register TwigComponentBundle (`actions`, `forms`, `notifications`, `panels`, `schemas`, `tables`) render them through their own templates. This is deliberate, and `packages/forms/tests/Integration/Components/InputWrapperRenderTest.php` notes it. Revisit it only if support grows its own Twig-component test kernel.

Coverage follows whichever package first needed a component, so no single suite has it all. Derive the current map rather than trusting a written list:

```bash
for c in packages/support/templates/components/*/; do
  n=$(basename "$c")
  echo "$n: $(grep -rlE "SylamentSupport:${n}([ />]|\$)" packages/*/templates packages/*/tests \
    | sed -E 's#packages/([a-z]+)/(templates|tests)/.*#\1:\2#' | sort -u | tr '\n' ' ')"
done
```

A hit under a package's `templates/` means that package's suite renders the component. So does every suite that renders that package's templates in turn: `forms` renders schemas' `Tabs` partial, and `panels` renders tables' `Table` partial and forms' controls. A snapshot of the direct hits, taken when this file was written:

| Component | Invoked from |
|---|---|
| Badge | actions, forms, panels, schemas, support, tables templates; actions tests |
| Breadcrumbs | panels templates |
| Button | actions, forms, panels, tables templates; actions tests |
| Dropdown | actions, panels, schemas, tables templates; actions tests |
| DropdownItem | actions, panels, schemas templates; actions tests |
| Fieldset | schemas templates and tests |
| Icon | forms, panels, support, tables templates; schemas and tables tests |
| IconButton | actions, notifications, panels, support, tables templates; schemas tests |
| InputWrapper | forms, panels, tables templates; forms tests |
| Link | actions, tables templates; actions tests |
| LoadingIndicator | schemas, tables templates |
| Modal | actions, tables templates; actions tests |
| Pagination | tables templates |
| Section | schemas templates and tests; actions tests |
| Tabs, TabsItem | panels, schemas templates |

`InputWrapper` is the case to remember: forms' text-like controls and tables' `TextInputColumn` and `SelectColumn` cells both render it directly, so a change to it runs against both suites.

## Why does a test that asserts a toast run as a live request?

A notification is a session flash: `Notification::send()` reads the session off the `RequestStack`, and an in-process call on a host object has no request carrying one, so `callMountedAction()` throws `SessionNotFoundException` before it can toast. A test that asserts a toast, or anything the toast's re-render paints, therefore drives the host through `InteractsWithLiveComponents` (`createLiveComponent()` and `call()`), as `packages/actions/tests/Integration/CreateActionTest.php` does. A case that only checks state stays in-process, which is cheaper. The live cases disable the kernel reboot between requests, because the fixture database lives in memory on a container service and a reboot would hand the next request a freshly migrated, empty one.

## Which fixtures carry a composite identifier?

Every package that keys a record exercises both composite flavours in a test: two scalar fields, and an association plus a scalar. `CompositePair` (two scalar fields) sits in schemas', forms' and tables' `tests/Fixtures/Doctrine/Entity/`, and `Membership` (an association plus a scalar) in actions'. A new by-key site is tested over both.

Panels has a family of its own: `Catalogue`, the single-identifier parent and owner; `Stamp`, that parent association plus a converting `string` code; `Colophon`, one identifier association alone — a single derived identity, the flavour an `isIdentifierComposite` check misses; `Imprint`, two strings seeded with `.`, `/`, a space, `~`, `=` and non-ASCII; and `Edition`, an `id` plus a `locale`. Their resources mount on a non-default `key` panel of their own (`tests/Fixtures/KeyPanelProvider.php`), because the navigation and global-search suites' enumerating assertions name the `admin` and `nest` panels, and a second default panel would reorder the one they resolve.

The codec's own shape battery, with a fixture per supported identifier shape, is described in [record-key.md](../progress/schemas/record-key.md#the-shapes-the-fixtures-pin).
