---
paths:
  - "packages/*/tests/**"
description: What a mechanical edit to a placeholder per-package Pest suite needs, from its hooks to its base class.
---
# Legacy test rules

These suites take mechanical edits only; a new test goes to `/tests`.

- **`tests/Pest.php` owns the hooks.** It extends `TestCase` `->in('Feature')`, and `TestCase` extends `LegacyTestCase`, whose `tearDown()` unsets every static instance after a test that booted the kernel; never add a per-file unset hook, since that reset already runs.

Background: `docs/internals/test-infrastructure.md`.
