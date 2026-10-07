---
description: The actions package's enums, which hold ActionStatus, the outcome an action reports after it runs.
ports:
  - vendor/filament/actions/src/Enums/ActionStatus.php
owns:
  - packages/actions/src/Enums/
---
# Enums (actions)

[`ActionStatus`](../../../packages/actions/src/Enums/ActionStatus.php) is what [`Action::getStatus()`](action.md) returns, and [`HasActions::callMountedAction()`](has-actions.md) reads it to choose between the success and the failure notification and redirect.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`ActionStatus` — `Success`, `Failure`](../../../packages/actions/src/Enums/ActionStatus.php) | [`Enums/ActionStatus.php`](../../../vendor/filament/actions/src/Enums/ActionStatus.php) | done | — | — |
