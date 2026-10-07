---
description: The trait that builds and sends an action's success and failure notifications through the optional notifications package.
ports:
  - vendor/filament/actions/src/Concerns/CanNotify.php
owns:
  - packages/actions/src/Concerns/CanNotify.php
---
# CanNotify (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`successNotificationTitle()` / `getSuccessNotificationTitle()` / `successNotification()` / `sendSuccessNotification()`](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | done | — | [semantics](#the-override-receives-the-built-notification-and-a-title-decides-the-send) · [diverges](#the-notification-arm-is-a-guarded-soft-integration-not-a-require) |
| [`failureNotificationTitle()` / `getFailureNotificationTitle()` / `failureNotification()` / `sendFailureNotification()`](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | done | — | [semantics](#the-override-receives-the-built-notification-and-a-title-decides-the-send) · [diverges](#the-notification-arm-is-a-guarded-soft-integration-not-a-require) · [diverges](#the-warning-arm-also-needs-the-selected-records-access-flag) |
| `failureNotificationBody()` and the custom-body arm of `getFailureNotificationBody()` | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | todo | — | [detail](#the-failure-notification-carries-no-body) |
| [`getFailureNotificationNamedInjections()`'s four counting injections](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | done | — | — |
| `getFailureNotificationNamedInjections()`'s five message injections | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | blocked | the keyed message buckets and `CanBeAuthorized` | [detail](#the-failure-notification-carries-four-injections-of-filaments-nine) · [bulk-action.md](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages) |
| `missingBulkAuthorizationFailureNotificationMessage()` / `missingBulkProcessingFailureNotificationMessage()` and the message list of `getFailureNotificationBody()` | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | blocked | the keyed message buckets | [detail](#the-failure-notification-carries-four-injections-of-filaments-nine) · [bulk-action.md](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages) |
| `unauthorizedNotification()` / `unauthorizedNotificationTitle()` / `getUnauthorizedNotificationTitle()` / `sendUnauthorizedNotification()` | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | blocked | `CanBeAuthorized` | [detail](#the-unauthorized-and-rate-limited-notifications-wait-on-their-concerns) |
| `rateLimitedNotification()` / `rateLimitedNotificationTitle()` / `getRateLimitedNotificationTitle()` / `sendRateLimitedNotification()` | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | blocked | `CanBeRateLimited` | [detail](#the-unauthorized-and-rate-limited-notifications-wait-on-their-concerns) |
| `successNotificationMessage()` / `failureNotificationMessage()` (deprecated) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | skipped | — | [diverges](#the-deprecated-notificationmessage-aliases-are-not-ported) |

## Details

### The failure notification carries no body

Filament's `failureNotificationBody()` stores a string or a closure, and `getFailureNotificationBody()` evaluates it with the failure injections and hands it to the failure notification's `->body()`.
The port has neither, so `sendFailureNotification()` builds a notification with a status, a title and `persistent()` and nothing else.
[`Notification::body()`](../../../packages/notifications/src/Concerns/HasBody.php) exists and the toast renders it through `sanitize_html` ([notification.md](../notifications/notification.md#the-default-sanitizer-is-stricter-than-the-one-filament-tunes)), so a caller's own body has nothing to wait on.
The message list `getFailureNotificationBody()` falls back to is a separate blocked row, because it reads the keyed message buckets.

### The failure notification carries four injections of Filament's nine

[`getFailureNotificationNamedInjections()`](../../../packages/actions/src/Concerns/CanNotify.php) returns `failureCount`, `isAll`, `successCount` and `totalCount`, the counts a failure title closure needs; [`DeleteBulkAction`](../../../packages/actions/src/DeleteBulkAction.php)'s failure title reads `successCount` and `totalCount`, as Filament's does.
`getFailureNotificationTitle()` and the `failureNotification()` override both receive them.
Filament returns five more, all keyed off machinery the port does not carry: `authorizationFailureMessages` and `missingAuthorizationFailureMessageCount` read per-record authorization ([bulk-action.md](bulk-action.md#per-record-authorization-waits-until-authorization-lands)), and `processingFailureMessages`, `failureMessages` and `missingProcessingFailureMessageCount` read the [keyed message buckets](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages).
`getFailureNotificationBody()`'s message list and the two `missingBulk*FailureNotificationMessage()` setters are absent for the same reason.
The list is also an escaping decision: Filament wraps each message, which a user closure can supply, in `"<p>{$message}</p>"`, so the port has to decide whether a reported message is markup or text before it concatenates one.
The keyed buckets, the five injections and the message list land together, with that decision written down first.

### The unauthorized and rate-limited notifications wait on their concerns

Filament's host sends `sendUnauthorizedNotification()` when an authorization check refuses a mount, passing Laravel's `Illuminate\Auth\Access\Response`, and `sendRateLimitedNotification()` when a call is throttled, passing `DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException`.
The actions port has no `CanBeAuthorized` and no `CanBeRateLimited` (both are unported rows in the [package README](README.md)), so neither notification has a caller or an argument to read.
Each is the same build, evaluate and send body the success and failure arms run, so each lands with its concern.

## Semantics

### The override receives the built notification, and a title decides the send

`sendSuccessNotification()` and `sendFailureNotification()` run Filament's order: return early when the disable flag is set; build `Notification::make()->success()`, or the failure status with `->persistent()`, and title it; evaluate the `successNotification()` or `failureNotification()` override with that notification injected as `notification`; and `send()` only when the result carries a title.
A `null` return from the override keeps the built notification, and any other return replaces it.
The failure override also receives the failure injections beside `notification`, so a `failureNotification()` closure reads the same counts its title closure does ([above](#the-failure-notification-carries-four-injections-of-filaments-nine)).
Because the title is read after the override runs, an override may title an untitled notification into a send, and a closure returning `$notification->title(null)` suppresses it.
`successNotification(null)` and `failureNotification(null)` are the other way to say *not this one*: they set the disable flag and skip the build outright.
The flash-to-browser step is the notifications package's: [`LiveDeliverySubscriber`](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php) turns each flashed entry into a `sylament:notification` browser event on a live request, so nothing here dispatches.

## Divergences

### The notification arm is a guarded soft integration, not a `require`

Filament's `filament/actions` **requires** `filament/notifications`, so its `CanNotify` imports `Notification` and calls it unconditionally.
The port cannot copy that edge: `sylament/notifications` requires `sylament/actions` (a notification carries `Action`s in its body), so a `require` back would be a composer cycle.
So the send arm takes the shape the repo reserves for exactly this case, the one [`HasActions`' table branch](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) uses and the one support's `isAssetMapperAvailable()` established:

- **the guard** — `class_exists(Notification::class)` in front of every use, in the same early return as the disable flag.
  `Notification::class` on an imported name is a compile-time string and autoloads nothing, so in an install without the package the symbol is never resolved and both send methods are chainable no-ops.
  The object arms *type* two properties and two setter parameters `Notification|Closure|null`, which is inert for the same reason: PHP resolves a property or parameter type lazily, only when a value meets it, and a caller with a `Notification` to pass has the package.
- **the declaration** — `sylament/notifications` is a composer `suggest` on `sylament/actions`, the strongest declaration an optional integration gets.
- **the pin** — a soft reference fails *silently* when it rots (a renamed or moved `Notification` leaves `class_exists()` false and every toast quietly disappears), so it is pinned by [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php) in the **notifications** Integration suite, whose kernel boots both bundles.

The guard's known limit, stated because it is loud rather than silent: it tests for the *package*, and sending also needs the *bundle* booted, since [`Notification::send()`](../notifications/notification.md) throws unless the instance came from the container.
An application that installs `sylament/notifications` but never registers `SylamentNotificationsBundle` therefore gets a `LogicException` naming that exact fix on the first successful action, instead of a missing toast.
That is the intended trade — a fallback that swallowed it would leave a user with a silently notification-less panel and no signal at all — and no guard can detect an unregistered bundle from inside a trait.

The second limit is the same shape one layer down: with the package installed *and* the bundle registered, `send()` still writes to the flash bag, and `RequestStack::getSession()` throws `SessionNotFoundException` on a request that carries none.
So a successful action on a session-less request — a console command, a stateless API route, an application that never enabled `framework.session` — fails there rather than silently dropping the toast.
Same trade, same reason: a panel application needs a session anyway, and the throw names the missing piece.

### The warning arm also needs the selected-records access flag

Filament's failure notification is `warning()` whenever `successfulSelectedRecordsCount` is non-zero and `danger()` otherwise, chained through `->when()`.
The port sends `warning()` only when `canAccessSelectedRecords()` is also true, written as an `if`/`else` because the port's `Notification` has no `when()`.
The two differ only for an action that does not access selected records yet reports a successful count, such as an ordinary action calling `reportBulkProcessingSuccessfulRecordsCount()`: Filament warns, and the port keeps the failure a `danger()` toast, so a partial-failure status appears only where there is a selection to be partial about.

### The deprecated `*NotificationMessage()` aliases are not ported

Filament's `successNotificationMessage()` and `failureNotificationMessage()` are `@deprecated` one-line forwards to `successNotificationTitle()` and `failureNotificationTitle()`, which the port carries.
The aliases are left out under the policy [can-dispatch-event.md](can-dispatch-event.md#the-deprecated-emit-aliases-are-not-ported) states for every deprecated alias.
