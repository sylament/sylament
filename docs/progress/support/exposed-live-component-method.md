---
description: The method attribute that marks a schema-component method as callable from the browser through the host's dispatcher.
ports:
  - vendor/filament/support/src/Components/Attributes/ExposedLivewireMethod.php
owns:
  - packages/support/src/Components/Attributes/
---
# ExposedLiveComponentMethod (support)

The attribute does nothing by itself: [`HasSchemas::callSchemaComponentMethod()`](../../../packages/schemas/src/Concerns/HasSchemas.php) reflects on the target method and calls it only when it carries the attribute ([has-schemas.md](../schemas/has-schemas.md)).
Filament's second reader, tables' `callTableColumnMethod()`, is a blocked row in [has-columns.md](../tables/has-columns.md#surface).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`#[ExposedLiveComponentMethod]`](../../../packages/support/src/Components/Attributes/ExposedLiveComponentMethod.php), a method-targeted attribute | [`Components/Attributes/ExposedLivewireMethod.php`](../../../vendor/filament/support/src/Components/Attributes/ExposedLivewireMethod.php) | done | — | [translations](../translations.md#idiom) |
