# Notifications — port trail

Ports [`filament/notifications`](../../../vendor/filament/notifications) into [`packages/notifications`](../../../packages/notifications): the notification builder, the toast stack it delivers to, and the database and real-time deliveries.

## Files

<!-- trail-index:start -->
- [notification.md](notification.md)
- [notifications-bundle.md](notifications-bundle.md)
- [notifications-icon-alias.md](notifications-icon-alias.md)
- [notifications.md](notifications.md)
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`Livewire/DatabaseNotifications.php`](../../../vendor/filament/notifications/src/Livewire/DatabaseNotifications.php) | blocked | notification storage | [notifications.md](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported) |
| [`BroadcastNotification.php`](../../../vendor/filament/notifications/src/BroadcastNotification.php) | blocked | the broadcast delivery arm | [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| [`DatabaseNotification.php`](../../../vendor/filament/notifications/src/DatabaseNotification.php) | blocked | notification storage | [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| [`Collection.php`](../../../vendor/filament/notifications/src/Collection.php) | skipped | — | [notifications.md](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported) |
| [`Events/DatabaseNotificationsSent.php`](../../../vendor/filament/notifications/src/Events/DatabaseNotificationsSent.php) | blocked | the broadcast delivery arm | [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| [`Testing/TestsNotifications.php`](../../../vendor/filament/notifications/src/Testing/TestsNotifications.php) | todo | — | [notification.md](notification.md#the-assertion-helpers-read-a-collection-that-no-longer-exists) |
| [`Testing/helpers.php`](../../../vendor/filament/notifications/src/Testing/helpers.php) | todo | — | [notification.md](notification.md#the-assertion-helpers-read-a-collection-that-no-longer-exists) |
| [`resources/js/Notification.js`](../../../vendor/filament/notifications/resources/js/Notification.js) | todo | — | [notifications.md](notifications.md#the-client-side-builders-entry-point-waits-on-the-builder) |
| [`views/database-notifications.blade.php`](../../../vendor/filament/notifications/resources/views/database-notifications.blade.php) | blocked | the database-notifications panel | [notifications-bundle.md](notifications-bundle.md#the-database-notifications-component-registration) |
| [`css/database-notifications.css`](../../../vendor/filament/notifications/resources/css/database-notifications.css) | blocked | the database-notifications panel | [notifications-bundle.md](notifications-bundle.md#the-database-notifications-component-registration) |
| [`resources/lang/`](../../../vendor/filament/notifications/resources/lang) | todo | — | [notifications-bundle.md](notifications-bundle.md#translations) |
