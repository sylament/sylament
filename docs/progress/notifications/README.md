# Notifications — port progress

Ports [`filament/notifications`](../../../vendor/filament/notifications) into
[`packages/notifications`](../../../packages/notifications). The notification
builder, toasts, and database / real-time delivery.

**Status:** todo — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure only; nothing has been audited yet.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

## Components

`Notification` and its delivery shapes —
[`src/Notification.php`](../../../vendor/filament/notifications/src/Notification.php),
[`src/DatabaseNotification.php`](../../../vendor/filament/notifications/src/DatabaseNotification.php),
[`src/BroadcastNotification.php`](../../../vendor/filament/notifications/src/BroadcastNotification.php),
[`src/Livewire`](../../../vendor/filament/notifications/src/Livewire).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Features

Cross-cutting concerns and traits —
[`src/Concerns`](../../../vendor/filament/notifications/src/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Events, templates, assets, test helpers —
[`src/Events`](../../../vendor/filament/notifications/src/Events),
[`src/Testing`](../../../vendor/filament/notifications/src/Testing),
[`src/View`](../../../vendor/filament/notifications/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
