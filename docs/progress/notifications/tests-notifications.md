---
description: The matching logic behind the tester's notification assertions, and the test-mode recorder that keeps what a request sent.
ports:
  - vendor/filament/notifications/src/Testing/TestsNotifications.php
owns:
  - packages/notifications/src/Testing/NotificationRecorder.php
  - packages/notifications/src/Testing/TestsNotifications.php
---
# TestsNotifications (notifications)

[`Sylament\Notifications\Testing\TestsNotifications`](../../../packages/notifications/src/Testing/TestsNotifications.php) holds Filament's `Notification::assertNotified()` and `assertNotNotified()` as two statics over a list of sent notifications, and [`NotificationRecorder`](../../../packages/notifications/src/Testing/NotificationRecorder.php) is where that list comes from.
A test calls neither: actions' [`Testable`](../actions/testable.md) collects what the recorder kept after each of its requests and passes it to the statics from its own `assertNotified()` and `assertNotNotified()`.
Filament's `TestsNotifications` is a Livewire `Testable` mixin wrapping the two statics; here the statics are a class, because actions' tester cannot use a trait from `sylament/notifications`, which actions only suggests.
Why the list is a recorder's and not the session's is [Notification's divergence](notification.md#the-assertion-helpers-read-what-a-tests-requests-sent-not-a-stack-collection).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`assertNotified()` / `assertNotNotified()`, by any notification, a title or a `Notification`](../../../packages/notifications/src/Testing/TestsNotifications.php) | [`Testing/TestsNotifications.php`](../../../vendor/filament/notifications/src/Testing/TestsNotifications.php), [`Notification::assertNotified()` / `assertNotNotified()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [semantics](#a-title-matches-the-sent-title-and-a-notification-everything-but-the-id) |
| [`NotificationRecorder`, decorating `SessionNotifications`](../../../packages/notifications/src/Testing/NotificationRecorder.php) | — (no Filament source) | done | — | [semantics](#the-recorder-exists-only-when-frameworktest-is-on-and-answers-for-one-request) |

## Semantics

### A title matches the sent title, and a `Notification` everything but the id

With no argument, `assertNotified()` passes when any notification was sent and `assertNotNotified()` when none was.
A string matches a sent notification's title, and a `Notification` matches one whose `toArray()` equals its own but for the id, as in Filament.
The recorded title is the [translated text](notification.md#a-title-or-body-message-is-translated-at-send-and-flashed-as-text), so a string is the title in the test locale, and neither assertion accepts a message.
The five failure messages are Filament's: `A notification was expected but none were sent.`, `A notification was not sent`, `No notification was expected but at least one was sent.`, `The notification with the given configuration was sent` and `The notification with the given title was sent`.
The tester's two assertions consume the list they read, as Filament's pull from the session empties it, so each means "sent by the tester's requests since the last notification assertion".

### The recorder exists only when `framework.test` is on, and answers for one request

[`SylamentNotificationsBundle`](notifications-bundle.md) registers `NotificationRecorder` as a public decorator of `SessionNotifications`, tagged `kernel.reset` and `sylament.test_only`, so support's [`RemoveTestOnlyServicesPass`](../support/support-bundle.md#a-test-only-service-is-removed-unless-frameworktest-is-on) removes it outside test mode before the decoration is resolved, as it removes actions' [exception recorder](../actions/testable.md#the-recorder-exists-only-when-frameworktest-is-on).
It records each `push()` and passes it on, so the stack still drains the toast into the live response, or keeps it in the flash across a redirect.
It extends `SessionNotifications` without running its constructor, so every public method of `SessionNotifications` must be overridden and delegated to `.inner`: an inherited one would touch the parent's uninitialised state or bypass `.inner`.
A client that keeps one kernel (`disableReboot()`) has the kernel reset the recorder as the next request starts, and a rebooted kernel builds a new one, so a read after a request sees that request's notifications only and the tester never collects one twice.

## Divergences

### The Pest function is not ported

`Testing/helpers.php` wraps `assertNotified()` as a Pest function returning `test()`.
It reaches the running test globally, which the port's testing API never does, and the tester's chained `assertNotified()` already covers it.
