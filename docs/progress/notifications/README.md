# Notifications — port progress

Ports [`filament/notifications`](../../../vendor/filament/notifications) into [`packages/notifications`](../../../packages/notifications).
The notification builder, toasts, and database / real-time delivery.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and [what goes where](../README.md#one-row-per-file-one-file-per-thing).

These tables map the full `notifications/src` surface end to end — all 23 files — plus the rendered views, the stylesheets, the scripts and the translations, so no table carries an [unaudited row](../README.md#an-unaudited-table-says-so).
The minimal notifications vertical is what has landed: `Notification::make()->…->send()` delivered as a toast stack, on page loads through the session flash and on live round trips through a browser event carrying server-rendered HTML.
A linked first-column name is built and carries a real [status](../README.md#statuses); a plain-text name is not built yet and is **todo**, **blocked** or **skipped**.

**blocked** rows outnumber **todo** ones here, and the split is the [vocabulary's](../README.md#statuses) rather than a mood: three whole arms of Filament's notifications package — the database panel, database storage and broadcast — are wanted for parity and each waits on infrastructure the port does not have yet (a notifications entity and the live panel that lists it; a Symfony transport for broadcast, where Mercure is the direction).
A row whose detail section names one of those is **blocked**; a row waiting on nothing at all — `date()`, `inline()`, custom views, the client-side builder, the test helpers, translations — is **todo**.

Six ported concerns are **folded** into [`notification.md`](notification.md) and carry no row below, per [one row per file](../README.md#one-row-per-file-one-file-per-thing): `HasId`, `HasTitle`, `HasBody`, `HasStatus`, `HasDuration` and `HasActions`, each with exactly one consumer today.
Filament's `Concerns\HasIcon` and `Concerns\HasIconColor` fold there too — they are status fallbacks over support's traits, and this port [resolves them in the template](notification.md#the-status-fallbacks-for-icon-and-icon-colour-live-in-the-template); so do the two unported concerns (`HasDate` and `CanBeInline`), which are rows in that file's surface table because that is where their write-ups are.
Both Blade views fold the same way: Filament renders a toast from `Notification::toEmbeddedHtml()` rather than a view file, so the toast partial is a row in [`notification.md`](notification.md), and [`notifications.blade.php`](../../../vendor/filament/notifications/resources/views/notifications.blade.php) is the stack's container markup, folded into [`notifications.md`](notifications.md).
[`support/component.md`](../support/component.md) is the shape each detail file follows.

## Components

`Notification`, the stack, and the delivery shapes — [`src/Notification.php`](../../../vendor/filament/notifications/src/Notification.php), [`src/DatabaseNotification.php`](../../../vendor/filament/notifications/src/DatabaseNotification.php), [`src/BroadcastNotification.php`](../../../vendor/filament/notifications/src/BroadcastNotification.php), [`src/Livewire`](../../../vendor/filament/notifications/src/Livewire).
A **blocked** row names its prerequisite at the head of _Detail_, ahead of any detail-file link.

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Notification`](../../../packages/notifications/src/Notification.php) | [`src/Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | in progress | [notification.md](notification.md) |
| [`Notifications`](../../../packages/notifications/src/Twig/Components/Notifications.php) (the stack) | [`Livewire/Notifications.php`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | in progress | [notifications.md](notifications.md) |
| DatabaseNotifications | [`Livewire/DatabaseNotifications.php`](../../../vendor/filament/notifications/src/Livewire/DatabaseNotifications.php) | blocked | notification storage — the panel is a separate piece of work, with its own live component ([why the stack's shape does not paint it into a corner](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported)) |
| BroadcastNotification | [`BroadcastNotification.php`](../../../vendor/filament/notifications/src/BroadcastNotification.php) | blocked | the broadcast delivery arm — [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| DatabaseNotification | [`DatabaseNotification.php`](../../../vendor/filament/notifications/src/DatabaseNotification.php) | blocked | notification storage — [notification.md](notification.md#the-broadcast-and-database-delivery-arms-are-deferred-whole) |

## Features

Cross-cutting concerns and traits — [`src/Concerns`](../../../vendor/filament/notifications/src/Concerns).

This table is **audited and empty**, which is a statement rather than an omission: all ten files in Filament's `Concerns/` directory belong to `Notification` alone, so every one of them is a row in [`notification.md`](notification.md)'s surface table and none is promoted here.
A concern earns a row in this section the moment a **second** component composes it — the database-notifications panel is the first candidate, since it renders notifications inline and reads their dates.

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |

## Supporting surface

Everything with no notification class of its own: the bundle wiring and the delivery pair it owns, the icon aliases, the colour policies, the Livewire collection carrier, the broadcast event, the test helpers, the stylesheets, the scripts and the translations — [`src/Collection.php`](../../../vendor/filament/notifications/src/Collection.php), [`src/Events`](../../../vendor/filament/notifications/src/Events), [`src/NotificationsServiceProvider.php`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php), [`src/Testing`](../../../vendor/filament/notifications/src/Testing), [`src/View`](../../../vendor/filament/notifications/src/View), [`resources`](../../../vendor/filament/notifications/resources).
A row named for a directory collapses that whole subtree into one navigational entry.

The bundle owns the two `Delivery\` classes as well as its own wiring, so all three share [notifications-bundle.md](notifications-bundle.md): what `loadExtension()` wires and why Filament's provider has no counterpart for it, the flash key, and the `PreRenderEvent` subscriber that replaces the dehydrate hook.
The toast stylesheet and the two Stimulus controllers are split by which component they dress — the `.sy-no` container rules and the stack controller are rows in [notifications.md](notifications.md), the `.sy-no-notification` rules and the toast controller rows in [notification.md](notification.md) — so the rows here point at whichever file carries the gap.

Two rows below name the provider in _Ports_ — once for the bundle as a whole, once for the `on('dehydrate')` hook `LiveDeliverySubscriber` replaces — and carry different statuses, because they describe different things.
Neither is the hook's own status: that lives in [notifications-bundle.md](notifications-bundle.md)'s surface table, and both rows here are navigational entries pointing at it, per [one row per file](../README.md#one-row-per-file-one-file-per-thing).
Update the hook there; if these two ever disagree about it, that file wins.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`SylamentNotificationsBundle`](../../../packages/notifications/src/SylamentNotificationsBundle.php) | [`NotificationsServiceProvider.php`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | in progress | [notifications-bundle.md](notifications-bundle.md) |
| [`Delivery\SessionNotifications`](../../../packages/notifications/src/Delivery/SessionNotifications.php) | — (Sylament-only; Filament writes the session inline in two files) | done | [notifications-bundle.md](notifications-bundle.md#the-flash-bag-replaces-laravels-session-push-and-owns-the-key) |
| [`Delivery\LiveDeliverySubscriber`](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php) | [`on('dehydrate', …)`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | done | [notifications-bundle.md](notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) |
| [`NotificationsIconAlias`](../../../packages/notifications/src/Twig/NotificationsIconAlias.php) | [`View/NotificationsIconAlias.php`](../../../vendor/filament/notifications/src/View/NotificationsIconAlias.php) | in progress | [notifications-icon-alias.md](notifications-icon-alias.md) |
| [`Colors\Components\NotificationComponent`](../../../packages/notifications/src/Colors/Components/NotificationComponent.php) | [`View/Components/NotificationComponent.php`](../../../vendor/filament/notifications/src/View/Components/NotificationComponent.php) | done | an empty colour map is complete, not a stub — the two base classes swap the palette and the stylesheet picks the shades ([colour policies](../support/color-policies.md)) |
| [`Colors\Components\NotificationComponent\IconComponent`](../../../packages/notifications/src/Colors/Components/NotificationComponent/IconComponent.php) | [`View/Components/NotificationComponent/IconComponent.php`](../../../vendor/filament/notifications/src/View/Components/NotificationComponent/IconComponent.php) | done | same, for the toast's icon slot |
| [Styles](../../../packages/notifications/assets/src/styles) | [`resources/css/notification.css`](../../../vendor/filament/notifications/resources/css/notification.css), [`css/notifications.css`](../../../vendor/filament/notifications/resources/css/notifications.css), [`css/index.css`](../../../vendor/filament/notifications/resources/css/index.css) (the aggregator, ported by [`src/styles/index.css`](../../../packages/notifications/assets/src/styles/index.css)) | in progress | the container rules are complete; the toast rules lack the date and inline arms — [notification.md](notification.md#the-toast-markup-is-missing-the-date-and-inline-arms) |
| [Scripts](../../../packages/notifications/assets/src/controllers) | [`resources/js/components/notification.js`](../../../vendor/filament/notifications/resources/js/components/notification.js), [`js/index.js`](../../../vendor/filament/notifications/resources/js/index.js) | in progress | the two Stimulus controllers replace the Alpine plugin; `markAsRead()`/`markAsUnread()` are unported — [notification.md](notification.md#markasread-and-markasunread-belong-to-the-database-panel) — and so is the [`close-notification` listener](notification.md#closing-a-toast-by-id-needs-the-id-in-the-markup) |
| Collection | [`Collection.php`](../../../vendor/filament/notifications/src/Collection.php) | skipped | the stack holds no server-side state, so there is nothing for a `Wireable` carrier to carry — [notifications.md](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported) |
| Events / DatabaseNotificationsSent | [`Events/DatabaseNotificationsSent.php`](../../../vendor/filament/notifications/src/Events/DatabaseNotificationsSent.php) | blocked | the broadcast delivery arm — it is a `ShouldBroadcast` event on a private Echo channel |
| Testing / TestsNotifications | [`Testing/TestsNotifications.php`](../../../vendor/filament/notifications/src/Testing/TestsNotifications.php) | todo | [notification.md](notification.md#the-assertion-helpers-read-a-collection-that-no-longer-exists) |
| Testing / helpers | [`Testing/helpers.php`](../../../vendor/filament/notifications/src/Testing/helpers.php) | todo | [notification.md](notification.md#the-assertion-helpers-read-a-collection-that-no-longer-exists) |
| js / Notification.js (the client-side builder) | [`resources/js/Notification.js`](../../../vendor/filament/notifications/resources/js/Notification.js) | todo | its Sylament shape is a builder posting to a fragment-rendering endpoint — [notifications.md](notifications.md#the-client-side-builders-entry-point-waits-on-the-builder) |
| views / database-notifications | [`views/database-notifications.blade.php`](../../../vendor/filament/notifications/resources/views/database-notifications.blade.php) | blocked | the database-notifications panel |
| css / database-notifications | [`css/database-notifications.css`](../../../vendor/filament/notifications/resources/css/database-notifications.css) | blocked | the database-notifications panel |
| Translations | [`resources/lang/`](../../../vendor/filament/notifications/resources/lang) | todo | [notifications-bundle.md](notifications-bundle.md#translations) |
