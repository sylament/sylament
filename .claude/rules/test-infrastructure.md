---
paths:
  - "/tests/Fixtures/**"
  - "/tests/Infrastructure/**"
  - "/tests/TestCase.php"
  - "/tests/Pest.php"
  - "/phpunit.dist.xml"
description: How the root suite's kernel, configuration, fixture areas, writable paths and base class are laid out and kept parallel-safe.
---
# Test infrastructure rules

- **One kernel, one base class.** `phpunit.dist.xml` names `Sylament\Tests\Fixtures\Kernel` and `tests/Pest.php` extends `Sylament\Tests\TestCase` over the whole tree; never add a second of either, because a test on its own kernel exercises a world no other test sees.
- **Nothing boots until a test asks for the container,** through `$this::getContainer()` or a `TestCase` helper that calls it; a pure test stays pure because no base-class hook touches the kernel.
- **Area ownership is by directory.** A package's area owns `tests/<Area>/`, `tests/Fixtures/<Area>/`, `tests/Fixtures/templates/<area>/`, `tests/Fixtures/icons/<area>/`, `tests/Fixtures/config/routes/<area>.php` and `tests/Fixtures/config/packages/<area>.php`; everything else under `tests/` is shared, so two areas never edit one file.
- **Create a fixture in the lowest area that needs it** (support, schemas, forms, actions, tables, panels); a higher area uses it and never edits it, and adds its own when it needs something different.
- **Never edit the `Kernel` to register a fixture.** `config/services.php` loads `Sylament\Tests\Fixtures\` from `tests/Fixtures/` with autowire and autoconfigure, so a class dropped into an area folder is registered; fixtures stay private, since `test.service_container` reaches them.
- **Exclude a non-service directory from that glob** as `config/`, `templates/`, `icons/`, `assets/`, `importmap.php` and `*/Entity/` are, or the loader registers what it finds there.
- **Configure through files, one per extension:** `config/packages/<extension>.php`, `config/packages/<env>/*.php`, `config/services.php` and `config/routes/*.php`, the layout `MicroKernelTrait` imports from the kernel's config directory; the `Kernel` keeps only the bundle list, the writable paths and the route order.
- **Keep the route order:** `routes/live_component.php` (live routes under `/_components`), then the panel route loader, then every other `routes/*.php`; the kernel imports them in that order rather than by name.
- **Register every bundle in every environment.** An environment exists only for a configuration value that would change what other tests observe, is named after what it varies, and keeps its files in `config/packages/<env>/`; it never changes the bundle list.
- **Leave debug on** (`APP_DEBUG` unset), so a worker recompiles its container when a fixture changes.
- **Name templates and icons by area:** a template is `@Fixtures/<area>/…` and an icon `<area>:<name>` from `icons/<area>/`; one Twig namespace and one icon directory serve every area.
- **Write only under `WorkerDirectory::path()`** (`var/tests/<TEST_TOKEN or 0>`): the kernel's cache and logs, the session files, and the `primary.storage` and `secondary.storage` disks under `storage/`, reached in config as `%sylament_tests.worker_dir%`; never `sys_get_temp_dir()`, a fixed path or a source directory, because parallel workers would share it.
- **`TestCase::tearDown()` is the reset:** it unsets every loaded `StaticInstance`, booted or not, and removes the worker's `storage/`; never add an `afterEach` that repeats it.
- **No free helper functions.** Every test file shares one namespace, so two areas defining one name collide; a helper that needs the container is a `TestCase` method from a trait (a shared one under `tests/Infrastructure/`, an area's own `<Area>Helpers` in its fixture folder, each with one `use` line in `TestCase`), and a pure one is a static method.
- **Keep classes out of Pest's auto-loaded folders** (`tests/Expectations/`, `tests/Helpers/`): Pest `include_once`s every file there at boot and Composer autoloads the class again, so it is declared twice.
- **Infrastructure and fixtures are analysed like shipped code:** PHPStan covers `tests/` at level max except `*Test.php`, `tests/Pest.php` and `tests/Expectations/`, and a fixture deliberately ill-typed for the behaviour it exercises takes an identifier-scoped `@phpstan-ignore` with its reason.
- **Run the suite as CI does:** `composer test:pest` runs it `--parallel`, so a race shows the day it is written.

Background: `docs/internals/test-infrastructure.md`.
