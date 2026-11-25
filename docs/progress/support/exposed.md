---
description: The method attribute that marks a schema-component method as callable from the browser through the host's dispatcher.
ports:
  - vendor/filament/support/src/Components/Attributes/Exposed.php
owns:
  - packages/support/src/Components/Attributes/
---
# Exposed (support)

The attribute does nothing by itself: [`HasSchemas::callSchemaComponentMethod()`](../../../packages/schemas/src/Concerns/HasSchemas.php) reflects on the target method and calls it only when it carries the attribute or a subclass of it ([has-schemas.md](../schemas/has-schemas.md)).
Filament's second reader, tables' `callTableColumnMethod()`, is a blocked row in [has-columns.md](../tables/has-columns.md#surface).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`#[Exposed]`](../../../packages/support/src/Components/Attributes/Exposed.php), a method-targeted attribute | [`Components/Attributes/Exposed.php`](../../../vendor/filament/support/src/Components/Attributes/Exposed.php) | done | — | — |
