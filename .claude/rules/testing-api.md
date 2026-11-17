---
paths:
  - "packages/*/src/Testing/**"
description: When a testing-API helper is ported, what visibility and status it keeps, how it guards a suggested package, and what stays out of it.
---
# Testing-API rules

- **Port a helper, or an arm of one, in the commit whose test first uses it;** the rest keep their `todo` rows, because a port no test drives is API whose first real use may have to reshape it. Filament's upstream-deprecated aliases and its Pest `helpers.php` are `skipped`.
- **Keep Filament's visibility on a ported helper** (`parseNestedActions()` stays `public`) unless the trail entry records a Sylament reason, because a migrating application's tests and subclasses reach it as Filament declares it.
- **A helper or arm no committed case exercises stays `in progress`** with a `## Details` gap (`emit()`, `setRouteLocale()`), because a `done` row tells the reader a test pins it.
- **Take the guarded list before naming a suggested package's class** (`takeSentNotifications()` runs before `TestsNotifications::assertNotified()`): PHP resolves a static call's class before it evaluates the arguments, so a guard inside an argument surfaces as a class-not-found `Error`, never as the `LogicException` naming the package.
- **Keep selector helpers in the suite** (`tests/Fixtures/src/<Area>/Selectors.php`), never in `src/Testing`: shipping one freezes Sylament's markup as public API, which Filament has no counterpart for; the public API asserts state, records and notifications.

Background: `docs/internals/testing.md`, `docs/progress/actions/testable.md`.
