---
description: The StateCast contract, the get() and set() pair that every schemas state cast implements.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/Contracts/StateCast.php
owns:
  - packages/schemas/src/State/Casts/Contracts/
---
# StateCast (schemas)

[`Sylament\Schemas\State\Casts\Contracts\StateCast`](../../../packages/schemas/src/State/Casts/Contracts/StateCast.php) sits under `State\Casts\` where Filament's sits under `Components\StateCasts\`, per the [namespace translation](../translations.md#namespace-segments).
A component collects its casts through `stateCast()`, `getDefaultStateCasts()` and `getStateCasts()` on [`HasState`](component.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get(mixed $state)` / `set(mixed $state)`](../../../packages/schemas/src/State/Casts/Contracts/StateCast.php) | [`StateCasts/Contracts/StateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/Contracts/StateCast.php) | done | — | [semantics](#set-runs-on-the-way-into-the-raw-state-and-get-on-the-way-out) |

## Semantics

### `set()` runs on the way into the raw state, and `get()` on the way out

`HasState::state()` passes a written value through every cast's `set()`, last cast first, before it stores the raw state.
`getState()`, `getOldState()` and `getStateToDehydrate()` pass the raw state through every cast's `get()`, first cast first.
So `set()` produces what the raw bag holds, and `get()` produces what the component, its validation and the save see, as in Filament.
