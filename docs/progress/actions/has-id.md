---
description: The actions package's HasId concern, which gives an action an id that falls back to its name.
ports:
  - vendor/filament/actions/src/Concerns/HasId.php
owns:
  - packages/actions/src/Concerns/HasId.php
---
# HasId (actions)

[`Sylament\Actions\Concerns\HasId`](../../../packages/actions/src/Concerns/HasId.php) ports Filament's actions-package `HasId`, not the same-named concerns of schemas, notifications and panels.
No Sylament class composes it, because Filament composes it only on `SelectAction`, which has no port.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`id()` / `getId()`, falling back to `getName()`](../../../packages/actions/src/Concerns/HasId.php) | [`Concerns/HasId.php`](../../../vendor/filament/actions/src/Concerns/HasId.php) | done | — | — |
