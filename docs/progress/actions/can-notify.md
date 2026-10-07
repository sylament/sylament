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
| [`CanNotify`](../../../packages/actions/src/Concerns/CanNotify.php) | [`Concerns/CanNotify.php`](../../../vendor/filament/actions/src/Concerns/CanNotify.php) | in progress | — | [detail](#cannotifys-remainder-is-the-failure-body-and-the-two-keyed-off-families), [bulk-action.md](bulk-action.md) |

## Details

### CanNotify's remainder is the failure body and the two keyed-off families

**In:** `successNotificationTitle()` / `failureNotificationTitle()` and their `getSuccessNotificationTitle()` / `getFailureNotificationTitle()` evaluated getters, plus the object arms `successNotification()` / `failureNotification()`: the `Notification|Closure|null` setters that let a caller replace or amend the whole notification, each deriving `isSuccessNotificationDisabled` / `isFailureNotificationDisabled` from a `null` argument exactly as Filament derives them.
Method for method identical to Filament's non-bulk path, and covered by [`CanNotifyTest`](../../../packages/actions/tests/Unit/Concerns/CanNotifyTest.php) via the [`CanNotifyFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanNotifyFixture.php).
`sendSuccessNotification()` / `sendFailureNotification()` are called from [`HasActions::callMountedAction()`](../../../packages/actions/src/Concerns/HasActions.php) exactly where Filament's `callMountedAction()` calls them, and each now runs Filament's own order: return early when the matching disable flag is set or `class_exists(Sylament\Notifications\Notification::class)` is false; build `Notification::make()->success()` / `->danger()->persistent()` and title it; hand that built object to the override, evaluated with `notification` injected — and, on the failure side, with `getFailureNotificationNamedInjections()` spread in beside it, so a `failureNotification()` closure reads the same four counts (`failureCount`, `isAll`, `successCount`, `totalCount`) its title closure does — its return replacing the built one and a `null` return keeping it; and `send()` only when a title survives.
Those are two different ways to say *not this one* — `successNotification(null)` disables the send outright, and a closure returning `$notification->title(null)` suppresses it — and both are Filament's.
The `class_exists()` condition is a [guarded soft integration](#the-notification-arm-is-a-guarded-soft-integration-not-a-require), not a `require`: the imported symbol is reached only inside these two bodies, and naming it in a property or parameter *type* never triggers autoload.
Delivery from the flash to the browser is the notifications package's own job ([`LiveDeliverySubscriber`](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php) turns each flashed entry into a `sylament:notification` browser event on a live request), so nothing here dispatches.
The end-to-end pin lives in the notifications suite — [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php) — with tables' [`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php) proving the whole confirm→delete→toast shape.
Four fixture kernels register both bundles today (actions', notifications', panels' and tables'), so the write actions' own [`CreateActionTest`](../../../packages/actions/tests/Integration/CreateActionTest.php) and [`EditActionTest`](../../../packages/actions/tests/Integration/EditActionTest.php) read the `sylament:notification` event off the render here rather than one package over.

Three shape notes.
The title is checked **after** the `Notification` is built, where the port used to check it first and skip the build entirely.
The object arms are what forced the reorder: an override closure is entitled to title an otherwise untitled notification, and a pre-check would have skipped a send Filament makes.
The cost is one throwaway `Notification` per untitled, unoverridden action — accepted, and the reason the `class_exists()` guard now sits alone in the early return rather than sharing it with a `blank($title)`.
`persistent()` on the failure side is Filament's (`->persistent()` on its failure notification, so an error toast waits to be dismissed).
And the **partial-bulk `warning()` arm** is ported: an action that both accesses selected records and still has a non-zero successful count sends `warning()` rather than `danger()`, so "deleted 2 of 5" does not read as a total failure — written as an `if`/`else` where Filament chains `->when()`, since there is no `when()` on the port's `Notification`.
`getFailureNotificationTitle()` evaluates its closure with the four counting injections that go with it — see [bulk-action.md](bulk-action.md#the-failure-notification-carries-four-injections-of-filaments-nine) for what those are and which five of Filament's nine are deferred.
Both arms are pinned from the notifications suite's [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php), which is where the arm-by-arm coverage lives.

**Missing vs Filament:** `failureNotificationBody()` and the `->body(...)` call that reads it while building the failure notification.
`unauthorizedNotification()` / `unauthorizedNotificationTitle()` / `sendUnauthorizedNotification()` (keyed off Laravel's `Auth\Access\Response`) and `rateLimitedNotification()` / `rateLimitedNotificationTitle()` / `sendRateLimitedNotification()` (keyed off `DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException`) are also absent — there is no authorization-response or rate-limiting concern in the actions port yet for either to key off of, and each would otherwise be the same build-evaluate-send body the two arms above already run.
Of the bulk-only surface, `getFailureNotificationNamedInjections()` ships in its four-injection form while `missingBulkAuthorizationFailureNotificationMessage()`, `missingBulkProcessingFailureNotificationMessage()` and the bulk message formatting inside `getFailureNotificationBody()` wait on the [keyed message buckets](bulk-action.md#the-failure-reports-count-they-do-not-carry-keyed-messages).
The deprecated `successNotificationMessage()` / `failureNotificationMessage()` aliases are dropped outright — Filament itself deprecates them in favor of the `*Title()` setters this trait already carries, so nothing is lost.

**Next step:** add `failureNotificationBody()` and read it into the failure build.
`unauthorizedNotification*` and `rateLimitedNotification*` wait on an authorization-response equivalent and a rate-limiting concern respectively; the remaining five bulk injections wait on the keyed message buckets.

## Divergences

### The notification arm is a guarded soft integration, not a `require`

Filament's `filament/actions` **requires** `filament/notifications`, so its `CanNotify` imports `Notification` and calls it unconditionally.
The port cannot copy that edge: `sylament/notifications` already requires `sylament/actions` (a notification carries `Action`s in its body), so a `require` back would be a composer cycle.
So the send arm takes the shape the repo reserves for exactly this case — the one [`HasActions`' table branch](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) already uses, and the one support's `isAssetMapperAvailable()` established:

- **the guard** — `class_exists(Notification::class)` in front of every use.
  `Notification::class` on an imported name is a compile-time string and autoloads nothing, so in an install without the package the symbol is never resolved and both send methods stay the chainable no-ops they were.
  The object arms *type* two properties and two setter parameters `Notification|Closure|null`, which is inert for the same reason: PHP resolves a property or parameter type lazily, only when a value meets it — and a caller with a `Notification` to pass demonstrably has the package.
- **the declaration** — `sylament/notifications` is a composer `suggest` on `sylament/actions`, the strongest declaration an optional integration gets.
- **the pin** — a soft reference fails *silently* when it rots (a renamed or moved `Notification` leaves `class_exists()` false and every toast quietly disappears), so it is pinned by a test in the suite that has both packages: [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php) in the **notifications** Integration suite, whose kernel boots both bundles.
  Tables' [`PrebuiltDeleteActionTest`](../../../packages/tables/tests/Integration/PrebuiltDeleteActionTest.php) covers the same seam end to end.

The guard's known limit, stated because it is loud rather than silent: it tests for the *package*, and sending also needs the *bundle* booted, since [`Notification::send()`](../notifications/notifications.md) throws unless the instance came from the container.
An application that installs `sylament/notifications` but never registers `SylamentNotificationsBundle` therefore gets a `LogicException` naming that exact fix on the first successful action, instead of a missing toast.
That is the intended trade — a fallback that swallowed it would leave a user with a silently notification-less panel and no signal at all — and no guard can detect an unregistered bundle from inside a trait.

The second limit is the same shape one layer down: with the package installed *and* the bundle registered, `send()` still writes to the flash bag, and `RequestStack::getSession()` throws `SessionNotFoundException` on a request that carries none.
So a successful action on a session-less request — a console command, a stateless API route, an application that never enabled `framework.session` — fails there rather than silently dropping the toast.
Same trade, same reason: a panel application needs a session anyway, and the throw names the missing piece.
