---
description: The actions package's exceptions, which hold ActionNotResolvableException, the signal that a mounted action cannot be resolved.
ports:
  - vendor/filament/actions/src/Exceptions/ActionNotResolvableException.php
owns:
  - packages/actions/src/Exceptions/
---
# Exceptions (actions)

[`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php) is what the resolve pipeline in [`HasActions`](has-actions.md) throws when a mounted action's descriptor names nothing it can resolve, and `cacheMountedActions()` catches it so that the mount stack empties instead of failing the request, as in Filament.
The panels [resource `Page`](../panels/resource-pages.md) throws it too, for a record key that is not scalar.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`ActionNotResolvableException`](../../../packages/actions/src/Exceptions/ActionNotResolvableException.php) | [`Exceptions/ActionNotResolvableException.php`](../../../vendor/filament/actions/src/Exceptions/ActionNotResolvableException.php) | done | — | — |
