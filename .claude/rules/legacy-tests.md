---
paths:
  - "packages/*/tests/**"
description: What a mechanical edit to a per-package Pest suite needs, from its hooks to its kernel registration and fixture wiring.
---
# Legacy test rules

These suites take mechanical edits only; a new test goes to `/tests`.

- **`tests/Pest.php` owns the hooks.** It extends `TestCase` only `->in('Integration')`, and its `Unit` block unsets `ComponentManager` and `ComponentResolver` after each test; never add a per-file unset hook, since the registry's own reset already runs from `TestCase::tearDown()` for kernel tests.
- **Never edit the `Kernel` to register a fixture.** It loads all of `tests/Fixtures/` with autowire and autoconfigure, so dropping a class in the right folder is the whole registration.
- **Fixtures stay private.** `$this::getContainer()` returns `test.service_container`, which reaches private services; a fixture that must be public in the real container carries `#[Autoconfigure(public: true)]` on its own class, never wider Kernel defaults.
- **Special construction goes in a config file** at the `Fixtures/` root that the Kernel `import()`s, as `services_doctrine.php` does; the glob then excludes that file.
- **When a change lifts a refusal a legacy case pinned,** pin the support in the root suite and delete the legacy case, so the shape the refusal guarded stays covered where new tests go.

Background: `docs/internals/test-infrastructure.md`.
