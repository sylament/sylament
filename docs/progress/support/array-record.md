---
description: The global name of the entry that identifies an array record, `__key` unless an application overrides it.
ports:
  - vendor/filament/support/src/ArrayRecord.php
owns:
  - packages/support/src/ArrayRecord.php
---
# ArrayRecord (support)

Actions' [`InteractsWithRecord`](../actions/interacts-with-record.md) and tables' [`HasTable`](../../../packages/tables/src/Concerns/HasTable.php) read an array record's key from the entry this class names.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`keyName()`](../../../packages/support/src/ArrayRecord.php) | [`ArrayRecord::keyName()`](../../../vendor/filament/support/src/ArrayRecord.php) | done | — | — |
| [`getKeyName()`](../../../packages/support/src/ArrayRecord.php), defaulting to `__key` | [`ArrayRecord::getKeyName()`](../../../vendor/filament/support/src/ArrayRecord.php) | done | — | — |
