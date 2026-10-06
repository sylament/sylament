---
paths:
  - "packages/*/tests/**"
description: How a package's Pest suite is laid out, booted and wired, and where support's Twig components are render-tested.
---
# Test rules

- **Three buckets.** `Unit/` boots no kernel, `Integration/` boots it or reaches the container, and the optional `Architecture/` scans files rather than exercising a class; the bucket decides the base class, so a test in the wrong one runs on the wrong base.
- **Mirror `src/` exactly** inside `Unit/` and `Integration/`, and name the file after its class (`src/DependencyInjection/Compiler/StaticInstancePass.php` → `tests/Unit/DependencyInjection/Compiler/StaticInstancePassTest.php`); a mirrored test is found without searching.
- **Two allowed departures:** a cross-cutting Integration scenario sits beside the mirrored test under a scenario name (`CompositeRecordPagesTest`), and one class's cases may split across prefix-named files (`AssociationReader*Test`); anything else mirrors.
- **`tests/Pest.php` owns the hooks.** It extends `TestCase` only `->in('Integration')`, and its `Unit` block unsets `ComponentManager` and `ComponentResolver` after each test; never add a per-file unset hook, since the registry's own reset already runs from `TestCase::tearDown()` for kernel tests.
- **Fixtures mirror `src/`** under `tests/Fixtures/`, next to the subsystem they exercise; only the `Kernel` and its service-config files sit at the `Fixtures/` root.
- **Never edit the `Kernel` to register a fixture.** It loads all of `tests/Fixtures/` with autowire and autoconfigure, so dropping a class in the right folder is the whole registration.
- **Fixtures stay private.** `$this::getContainer()` returns `test.service_container`, which reaches private services; a fixture that must be public in the real container carries `#[Autoconfigure(public: true)]` on its own class, never wider Kernel defaults.
- **Special construction goes in a config file** at the `Fixtures/` root that the Kernel `import()`s, as `services_doctrine.php` does; the glob then excludes that file.
- **Use `$this::` for `KernelTestCase` statics** inside `it()`/`test()` closures (`$this::getContainer()`), never `static::` or `self::`: Pest binds `$this` to the configured `TestCase`, while `static::` resolves against the closure's scope.
- **Do not call `bootKernel()`** just to get a container, because `getContainer()` boots on demand; boot explicitly only to pass options (`$this::bootKernel(['environment' => 'icons'])`) or to hold the kernel itself.
- **Support's Twig components are render-tested from other suites,** because support's fixture kernel registers no TwigComponentBundle; `composer test:support` alone never renders them.
- **Before changing a support component template,** find its suites with `grep -rlE 'SylamentSupport:<Name>([ />]|$)' packages/*/templates packages/*/tests` and run each hit's suite plus those of packages rendering the hit's templates; `InputWrapper` has two direct homes, forms and tables.
- **The placeholder bundles** (`infolists`, `widgets`) keep their `Feature/ExampleTest.php` only until real tests land, which then take `Unit/` and `Integration/`; never add to `Feature/`.

Background: `docs/internals/test-infrastructure.md`.
