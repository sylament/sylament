---
description: Tables' tester, which adds tables' testing helpers to actions' tester, and the test-case trait that creates it.
ports: []
owns:
  - packages/tables/src/Testing/InteractsWithLiveComponents.php
  - packages/tables/src/Testing/Testable.php
---
# Testable (tables)

[`Sylament\Tables\Testing\Testable`](../../../packages/tables/src/Testing/Testable.php) has **no Filament source**: it extends actions' [`Testable`](../actions/testable.md) and is where tables' testing helpers ([`TestsRecords`](tests-records.md), [`TestsBulkActions`](tests-bulk-actions.md), [`TestsColumns`](tests-columns.md)) compose, since tables builds on actions, schemas and forms while they cannot see tables.
An install with `sylament/tables` uses tables' [`InteractsWithLiveComponents`](../../../packages/tables/src/Testing/InteractsWithLiveComponents.php), which uses actions' and narrows `createLiveComponent()` to return this tester; one without tables uses actions'.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Testable`, extending actions' tester](../../../packages/tables/src/Testing/Testable.php) | — (no Filament source) | done | — | — |
| [`InteractsWithLiveComponents::createLiveComponent()`, returning tables' tester](../../../packages/tables/src/Testing/InteractsWithLiveComponents.php) | — (no Filament source) | done | — | [actions' trait](../actions/testable.md#createlivecomponent-builds-uxs-tester-through-uxs-own-method) |
