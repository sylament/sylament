# Notifications — port trail

Ports [`filament/notifications`](../../../vendor/filament/notifications) into [`packages/notifications`](../../../packages/notifications): the notification builder, the toast stack it delivers to, and the database and real-time deliveries.

## Files

<!-- trail-index:start -->
| File | Status | Open | Ports | Description |
| --- | --- | --- | --- | --- |
| [notification.md](notification.md) | in progress | 2 in progress · 3 blocked · 5 todo | `Notification.php`, `Concerns/CanBeInline.php`, `Concerns/HasActions.php`, `Concerns/HasBody.php`, `Concerns/HasDate.php`, `Concerns/HasDuration.php`, `Concerns/HasIcon.php`, `Concerns/HasIconColor.php`, `Concerns/HasId.php`, `Concerns/HasStatus.php`, `Concerns/HasTitle.php`, `View/Components/NotificationComponent.php`, `View/Components/NotificationComponent/IconComponent.php`, `resources/css/notification.css`, `resources/js/components/notification.js` | The notification builder: its concerns, the array round trip, `send()`, the toast markup, its colour policies, stylesheet and Stimulus controller. |
| [notifications-bundle.md](notifications-bundle.md) | in progress | 2 in progress · 1 blocked · 1 todo | `NotificationsServiceProvider.php`, `composer.json`, `resources/css/index.css`, `resources/js/index.js` | The notifications bundle class, the session and live delivery pair it wires, its asset registration and the package manifest. |
| [notifications-icon-alias.md](notifications-icon-alias.md) | in progress | 1 blocked | `View/NotificationsIconAlias.php` | The overridable icon aliases the notifications package exposes, ported from Filament's `View\NotificationsIconAlias`. |
| [notifications.md](notifications.md) | in progress | 1 blocked · 1 todo | `Livewire/Notifications.php`, `resources/views/notifications.blade.php`, `resources/css/notifications.css` | The toast stack `<twig:SylamentNotifications:Notifications />`, placed once in an application layout, with its template and Stimulus controller. |
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`Livewire/DatabaseNotifications.php`](../../../vendor/filament/notifications/src/Livewire/DatabaseNotifications.php) | blocked | notification storage | [notifications.md](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported) |
| [`BroadcastNotification.php`](../../../vendor/filament/notifications/src/BroadcastNotification.php) | blocked | the broadcast delivery arm | [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| [`DatabaseNotification.php`](../../../vendor/filament/notifications/src/DatabaseNotification.php) | blocked | notification storage | [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| [`Collection.php`](../../../vendor/filament/notifications/src/Collection.php) | skipped | — | [notifications.md](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported) |
| [`Events/DatabaseNotificationsSent.php`](../../../vendor/filament/notifications/src/Events/DatabaseNotificationsSent.php) | blocked | the broadcast delivery arm | [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| [`Testing/TestsNotifications.php`](../../../vendor/filament/notifications/src/Testing/TestsNotifications.php) | todo | — | [notification.md](notification.md#the-assertion-helpers-read-a-collection-the-stack-does-not-hold) |
| [`Testing/helpers.php`](../../../vendor/filament/notifications/src/Testing/helpers.php) | todo | — | [notification.md](notification.md#the-assertion-helpers-read-a-collection-the-stack-does-not-hold) |
| [`resources/js/Notification.js`](../../../vendor/filament/notifications/resources/js/Notification.js) | todo | — | [notifications.md](notifications.md#the-client-side-builders-entry-point-waits-on-the-builder) |
| [`views/database-notifications.blade.php`](../../../vendor/filament/notifications/resources/views/database-notifications.blade.php) | blocked | the database-notifications panel | [notifications-bundle.md](notifications-bundle.md#the-database-notifications-registration-waits-on-its-panel) |
| [`css/database-notifications.css`](../../../vendor/filament/notifications/resources/css/database-notifications.css) | blocked | the database-notifications panel | [notifications-bundle.md](notifications-bundle.md#the-database-notifications-registration-waits-on-its-panel) |
| [`resources/lang/`](../../../vendor/filament/notifications/resources/lang) | todo | — | [notifications-bundle.md](notifications-bundle.md#the-translation-catalogue-is-unported-so-the-close-label-is-an-english-literal) |
