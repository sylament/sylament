---
description: The toast stack `<twig:SylamentNotifications:Notifications />`, placed once in an application layout, with its template and Stimulus controller.
ports:
  - vendor/filament/notifications/src/Livewire/Notifications.php
  - vendor/filament/notifications/resources/views/notifications.blade.php
  - vendor/filament/notifications/resources/css/notifications.css
owns:
  - packages/notifications/assets/src/controllers/notifications_controller.ts
  - packages/notifications/src/Twig/Components/
  - packages/notifications/templates/components/
---
# Notifications — the toast stack (notifications)

[`Sylament\Notifications\Twig\Components\Notifications`](../../../packages/notifications/src/Twig/Components/Notifications.php) is the `<twig:SylamentNotifications:Notifications />` stack, placed once in an application layout.
It ports Filament's [`Livewire\Notifications`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) component and its [`notifications.blade.php`](../../../vendor/filament/notifications/resources/views/notifications.blade.php) view, which fold together here: the class is a **class-backed Twig component**, not a live component, and its [template](../../../packages/notifications/templates/components/Notifications/index.html.twig) plus the stack Stimulus controller carry everything Filament's Livewire round trip carried.

That is the package's one architectural decision, and every divergence below is a face of it: the stack renders toasts, it does not *hold* them.
Toast HTML has one renderer (Twig), the DOM is the only client-side state, and a closed toast is gone rather than pending removal on a server that might morph it back.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The container — `role="status"`, `aria-atomic="false"`, `sy-no`](../../../packages/notifications/templates/components/Notifications/index.html.twig) | [`notifications.blade.php`](../../../vendor/filament/notifications/resources/views/notifications.blade.php) | done | — | — |
| [`alignment` prop → `sy-align-*`](../../../packages/notifications/src/Twig/Components/Notifications.php) | [`static $alignment` / `alignment()`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | done | — | [diverges](#stack-placement-is-tag-props-not-livewire-statics) |
| [`verticalAlignment` prop → `sy-vertical-align-*`](../../../packages/notifications/src/Twig/Components/Notifications.php) | [`static $verticalAlignment` / `verticalAlignment()`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | done | — | [diverges](#stack-placement-is-tag-props-not-livewire-statics) |
| [Container styles (`.sy-no`, the six placement variants)](../../../packages/notifications/assets/src/styles/components/notification.css) | [`resources/css/notifications.css`](../../../vendor/filament/notifications/resources/css/notifications.css) | done | — | — |
| [Page-load delivery — `getFlashed()` renders the flashed toasts inline](../../../packages/notifications/src/Twig/Components/Notifications.php) | [`mount()` → `pullNotificationsFromSession()`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | done | — | [semantics](#the-first-reader-of-the-flash-wins-and-both-readers-are-idempotent) |
| [Live delivery — the stack controller appends the event's HTML](../../../packages/notifications/assets/src/controllers/notifications_controller.ts) | [`#[On('notificationsSent')]` re-request](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | done | — | [diverges](#live-delivery-appends-server-rendered-html-instead-of-re-requesting-the-stack) |
| `removeNotification()` (`#[On('notificationClosed')]`) | [`Livewire/Notifications.php`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | skipped | — | [diverges](#a-toast-closes-client-side-and-is-never-re-rendered) |
| The `notifications` collection (`Filament\Notifications\Collection`, `Wireable`) | [`Collection.php`](../../../vendor/filament/notifications/src/Collection.php), [`public Collection $notifications`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | skipped | — | [diverges](#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported) |
| `$isFilamentNotificationsComponent` | [`Livewire/Notifications.php`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | skipped | — | [diverges](#the-flip-marker-prop-has-nothing-to-mark) |
| `pushNotificationFromEvent()` (`#[On('notificationSent')]`) | [`Livewire/Notifications.php`](../../../vendor/filament/notifications/src/Livewire/Notifications.php) | todo | — | [detail](#the-client-side-builders-entry-point-waits-on-the-builder) |
| `handleBroadcastNotification()` / `getBroadcastChannel()` / `getUser()` / `authGuard()` + the Echo `<script>` | [`Livewire/Notifications.php`](../../../vendor/filament/notifications/src/Livewire/Notifications.php), [`notifications.blade.php`](../../../vendor/filament/notifications/resources/views/notifications.blade.php) | blocked | the broadcast delivery arm | [detail](#the-broadcast-channel-arrives-with-the-broadcast-arm) |

## Details

### The client-side builder's entry point waits on the builder

`pushNotificationFromEvent()` listens for the Livewire `notificationSent` event that Filament's client-side `FilamentNotification` builder ([`resources/js/Notification.js`](../../../vendor/filament/notifications/resources/js/Notification.js)) dispatches: the browser composes a notification as data, the server rebuilds it through `Notification::fromArray()` and renders it.
The port has no client-side builder, so there is nothing to listen for.

**Next step:** port the builder and its entry point together, and note the shape the decision here implies — the stack renders no notification client-side, so the JS builder posts its payload to a **fragment-rendering endpoint** which returns toast HTML, rather than pushing state into a live component.
The builder's public API (`new FilamentNotification().title(…).success().send()`) is preservable either way.

### The broadcast channel arrives with the broadcast arm

Filament's stack subscribes to the authenticated user's private Echo channel: `getUser()` reads `auth(static::$authGuard)->user()`, `getBroadcastChannel()` derives the channel name from the user (or `receivesBroadcastNotificationsOn()`), the view emits an inline `<script>` that binds `window.Echo.private(…).notification(…)`, and `handleBroadcastNotification()` filters on `format === 'filament'` before pushing.
`authGuard()` exists only to select the guard for that lookup.

**Next step:** none until the broadcast delivery arm is built — the whole group lands with it, and the Symfony-side transport is Mercure rather than Echo.
`authGuard()` is deferred with the group rather than ported early: a guard name is meaningless without the channel it selects a user for.

## Semantics

### The first reader of the flash wins, and both readers are idempotent

Two things drain `sylament.notifications`: this component's `getFlashed()` on a full page load, and the [live-delivery subscriber](notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) on a live request.
Both call the same [`SessionNotifications::pull()`](../../../packages/notifications/src/Delivery/SessionNotifications.php), which is a flash-bag `get()` — it empties the bag as it reads.
So whichever runs first in a given request takes everything, and any later reader finds nothing: several live components rendering in one batched request produce one delivery, not one per component, and a page load that renders the stack twice renders the toasts once.
Filament reaches the same outcome because `session()->pull()` drains too, and adds a second `claimed_notifications` key between its hook and the stack's re-request; here it falls out of the flash bag's own semantics, with no second key to keep in sync.

## Divergences

### Stack placement is tag props, not Livewire statics

Filament configures the stack through three static setters called from a service provider — `Notifications::alignment(Alignment::Center)`, `verticalAlignment(...)`, `authGuard(...)` — because a Livewire component is mounted by name with no place for arguments.
Sylament's stack is a Twig tag, so the two placement settings are **props**: `<twig:SylamentNotifications:Notifications :alignment="'center'" :verticalAlignment="'end'" />`, each accepting the support enum ([`Alignment`](../../../packages/support/src/Enums/Alignment.php), [`VerticalAlignment`](../support/vertical-alignment.md)) or its string value, and each defaulting to Filament's default (`right` / `start` — a top-right stack).

Configuration therefore lives in the layout, which is the file the tag is in anyway, and nothing has to run at boot for a placement to take effect.
The enums and their values are Filament's, and the emitted `sy-align-*` / `sy-vertical-align-*` classes are Filament's `fi-align-*` / `fi-vertical-align-*` under the `fi-*` → `sy-*` class rename.
`authGuard()` is not a placement setting and is [deferred with the broadcast arm](#the-broadcast-channel-arrives-with-the-broadcast-arm) instead.

### The stack holds no server-side collection, so Filament's `Collection` is not ported

Filament's component keeps `public Collection $notifications` — a Livewire `Wireable` subclass of Laravel's collection whose whole job is de/hydrating an array of `Notification` objects across the wire — and the stack's screen is a render of that collection.
Sylament's stack holds nothing: `getFlashed()` returns the notifications this request found in the flash bag, renders them, and forgets them.
There is no property to hydrate, so there is nothing for a `Wireable` carrier to carry, and `Filament\Notifications\Collection` has no counterpart to port.

What that buys is the absence of a whole bug class.
A server-held collection has to stay in sync with the screen: closing a toast must tell the server, or the next render morphs it back; adding one costs a round trip; and the two can disagree in between.
Symfony UX's counterpart to a Livewire synthesizer is a tagged `HydrationExtensionInterface` (`supports()`, `hydrate()`, `dehydrate()`), so a server-held collection would also need one written for it rather than the `Wireable` pair `Collection` implements.

Permanent for the toast stack.
It says nothing about the **database-notifications panel**, which is a separate Livewire component in Filament too, with its own actions host and its own stored state, and is free to be a live component.

### Live delivery appends server-rendered HTML instead of re-requesting the stack

Filament's live path is: the dehydrate hook stashes the pending notifications and dispatches `notificationsSent`; the stack's `#[On('notificationsSent')]` listener fires a **second** Ajax request; that request pulls the session and re-renders the whole stack.
Sylament's [subscriber](notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) renders each toast server-side and queues a `sylament:notification` browser event carrying the HTML on the response already in flight; the [stack controller](../../../packages/notifications/assets/src/controllers/notifications_controller.ts) listens on `window` and appends it with `insertAdjacentHTML`.

One response instead of two, and no client-side renderer — the toast is Twig's output either way, so there is no second implementation to drift.
The trust level is the one a live morph already has (server-rendered markup written into the page by the framework's own transport), and the payload is type-checked before it is written: a `detail.html` that is not a string is ignored.

### A toast closes client-side and is never re-rendered

Filament's close is a round trip: the toast animates out, dispatches `notificationClosed`, and `removeNotification($id)` forgets it from the server's collection — necessary, because until the server forgets it any concurrent render morphs the toast back.
Sylament's [toast controller](../../../packages/notifications/assets/src/controllers/notification_controller.ts) collapses the element and removes it from the DOM, and that is the entire operation: the server never held it, so nothing can restore it.
`removeNotification()` is **skipped** rather than todo — there is no state for it to mutate.

### The FLIP marker prop has nothing to mark

`public bool $isFilamentNotificationsComponent = true` exists so Filament's Alpine plugin can recognise, inside a global `Livewire.interceptMessage` hook, that the message it just intercepted belongs to the notifications component and that the FLIP reposition should run.
The port replaces that hook with [CSS transitions](notification.md#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller), so nothing inspects a snapshot, and the marker has nothing to mark.
