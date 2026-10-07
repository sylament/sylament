---
description: 'The notification builder: its concerns, the array round trip, `send()`, the toast markup, its colour policies, stylesheet and Stimulus controller.'
ports:
  - vendor/filament/notifications/src/Notification.php
  - vendor/filament/notifications/src/Concerns/CanBeInline.php
  - vendor/filament/notifications/src/Concerns/HasActions.php
  - vendor/filament/notifications/src/Concerns/HasBody.php
  - vendor/filament/notifications/src/Concerns/HasDate.php
  - vendor/filament/notifications/src/Concerns/HasDuration.php
  - vendor/filament/notifications/src/Concerns/HasIcon.php
  - vendor/filament/notifications/src/Concerns/HasIconColor.php
  - vendor/filament/notifications/src/Concerns/HasId.php
  - vendor/filament/notifications/src/Concerns/HasStatus.php
  - vendor/filament/notifications/src/Concerns/HasTitle.php
  - vendor/filament/notifications/src/View/Components/NotificationComponent.php
  - vendor/filament/notifications/src/View/Components/NotificationComponent/IconComponent.php
  - vendor/filament/notifications/resources/css/notification.css
  - vendor/filament/notifications/resources/js/components/notification.js
owns:
  - packages/notifications/assets/src/controllers/notification_controller.ts
  - packages/notifications/assets/src/styles/components/
  - packages/notifications/src/Colors/
  - packages/notifications/src/Concerns/
  - packages/notifications/src/Notification.php
  - packages/notifications/templates/partials/
---
# Notification (notifications)

[`Sylament\Notifications\Notification`](../../../packages/notifications/src/Notification.php) is the notification builder: `Notification::make()->title('Saved')->success()->send()`.
It ports Filament's [`Filament\Notifications\Notification`](../../../vendor/filament/notifications/src/Notification.php), a `ViewComponent` subclass that sits at `src/` root the way actions' [`Action`](../actions/action.md) does.

Each ported concern has this class as its one consumer, so its rows sit in the table below per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature).
Filament's toast markup lives in `toEmbeddedHtml()` rather than in a Blade view, so its port, [`partials/Notification/index.html.twig`](../../../packages/notifications/templates/partials/Notification/index.html.twig), is documented here too, together with the toast's colour policies, its stylesheet and its Stimulus controller, which port `resources/css/notification.css` and the Alpine `notificationComponent` in `resources/js/components/notification.js`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` + the uuid id (`HasId`)](../../../packages/notifications/src/Concerns/HasId.php) | [`make()` / `__construct()`](../../../vendor/filament/notifications/src/Notification.php), [`Concerns/HasId.php`](../../../vendor/filament/notifications/src/Concerns/HasId.php) | done | — | [diverges](#make-mints-the-id-itself-because-the-resolver-takes-no-constructor-arguments) |
| [`title()` (`HasTitle`)](../../../packages/notifications/src/Concerns/HasTitle.php) | [`Concerns/HasTitle.php`](../../../vendor/filament/notifications/src/Concerns/HasTitle.php) | done | — | — |
| [`body()` (`HasBody`)](../../../packages/notifications/src/Concerns/HasBody.php) | [`Concerns/HasBody.php`](../../../vendor/filament/notifications/src/Concerns/HasBody.php) | done | — | — |
| [`status()` + `success()`/`info()`/`warning()`/`danger()` (`HasStatus`)](../../../packages/notifications/src/Concerns/HasStatus.php) | [`Concerns/HasStatus.php`](../../../vendor/filament/notifications/src/Concerns/HasStatus.php) | done | — | — |
| [`duration()` / `seconds()` / `persistent()` (`HasDuration`)](../../../packages/notifications/src/Concerns/HasDuration.php) | [`Concerns/HasDuration.php`](../../../vendor/filament/notifications/src/Concerns/HasDuration.php) | done | — | [diverges](#a-closure-duration-evaluating-to-null-throws-where-filament-falls-back-to-persistent) |
| [`icon()` / `iconSize()` + the status icon fallback](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`Concerns/HasIcon.php`](../../../vendor/filament/notifications/src/Concerns/HasIcon.php), support's `HasIcon`/`HasIconSize` | done | — | [diverges](#the-status-fallbacks-for-icon-and-icon-colour-live-in-the-template) · [semantics](#iconsize-does-not-survive-the-delivery-hop) |
| [`iconColor()` + the status colour fallback](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`Concerns/HasIconColor.php`](../../../vendor/filament/notifications/src/Concerns/HasIconColor.php), support's `HasIconColor` | done | — | [diverges](#the-status-fallbacks-for-icon-and-icon-colour-live-in-the-template) |
| [`color()` (support `HasColor`)](../../../packages/notifications/src/Notification.php) | [`Filament\Support\Concerns\HasColor`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`Colors\Components\NotificationComponent`](../../../packages/notifications/src/Colors/Components/NotificationComponent.php) — the toast's colour policy | [`View/Components/NotificationComponent.php`](../../../vendor/filament/notifications/src/View/Components/NotificationComponent.php) | done | — | [color-policies.md](../support/color-policies.md#an-empty-map-is-a-real-answer-not-a-missing-one) |
| [`Colors\Components\NotificationComponent\IconComponent`](../../../packages/notifications/src/Colors/Components/NotificationComponent/IconComponent.php) — the toast icon's colour policy | [`View/Components/NotificationComponent/IconComponent.php`](../../../vendor/filament/notifications/src/View/Components/NotificationComponent/IconComponent.php) | done | — | [color-policies.md](../support/color-policies.md#an-empty-map-is-a-real-answer-not-a-missing-one) |
| [`toArray()` / `fromArray()`](../../../packages/notifications/src/Notification.php) | [`toArray()` / `fromArray()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [diverges](#fromarray-drops-a-value-it-cannot-type-where-filaments-typed-setters-throw) · [diverges](#a-scalableicon-crosses-the-session-unflattened-and-resolves-at-render-size) · [diverges](#the-actions-key-is-a-list-on-both-sides-of-the-hop) |
| [`send()` → the session flash bag](../../../packages/notifications/src/Notification.php) | [`send()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [diverges](#send-always-writes-the-flash-bag-and-the-live-arm-decides-late) · [diverges](notifications-bundle.md#a-send-during-the-acting-components-own-render-waits-for-the-next-request) |
| [The toast markup — chrome, icon, title, body, actions, close button](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/notifications/src/Notification.php) | in progress | — | [detail](#the-toast-markup-is-missing-the-date-and-inline-arms) |
| [`role="alert"` on danger, polite otherwise](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [semantics](#the-assertive-split-is-per-toast-and-the-container-stays-polite) |
| [Title / body sanitization (`sanitize_html`)](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`str(...)->sanitizeHtml()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [semantics](#the-default-sanitizer-is-stricter-than-the-one-filament-tunes) |
| [Toast styles (`sy-no-notification*`)](../../../packages/notifications/assets/src/styles/components/notification.css) | [`resources/css/notification.css`](../../../vendor/filament/notifications/resources/css/notification.css) | in progress | — | [detail](#the-toast-markup-is-missing-the-date-and-inline-arms) · [diverges](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) |
| [Toast controller — auto-dismiss, hover-pause, `close()`, delegated close clicks](../../../packages/notifications/assets/src/controllers/notification_controller.ts) | [`resources/js/components/notification.js`](../../../vendor/filament/notifications/resources/js/components/notification.js) | done | — | [diverges](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) · [diverges](#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload) |
| `markAsRead()` / `markAsUnread()` | [`resources/js/components/notification.js`](../../../vendor/filament/notifications/resources/js/components/notification.js) | blocked | the database-notifications panel | [detail](#markasread-and-markasunread-belong-to-the-database-panel) |
| `close-notification` — closing a toast by id from a browser event | [`x-on:close-notification.window`](../../../vendor/filament/notifications/src/Notification.php) | todo | — | [detail](#closing-a-toast-by-id-needs-the-id-in-the-markup) |
| [`actions()` (`HasActions`)](../../../packages/notifications/src/Concerns/HasActions.php) | [`Concerns/HasActions.php`](../../../vendor/filament/notifications/src/Concerns/HasActions.php), the `actions` key of `toArray()`/`fromArray()` | done | — | [semantics](#toast-actions-are-re-viewed-as-small-links-and-the-actions-key-restores-them) · [semantics](#toast-actions-cannot-open-modals-at-filament-parity) · [diverges](#the-actions-row-is-styled-by-its-own-class-where-filament-reuses-the-cross-package-fi-ac) · [diverges](#the-re-view-defaults-a-group-to-teleport-where-filaments-toast-groups-clip) · [diverges](#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload) |
| `date()` (`HasDate`) | [`Concerns/HasDate.php`](../../../vendor/filament/notifications/src/Concerns/HasDate.php) | todo | — | [detail](#date-and-inline-arrive-with-the-database-notifications-panel) |
| `inline()` (`CanBeInline`) | [`Concerns/CanBeInline.php`](../../../vendor/filament/notifications/src/Concerns/CanBeInline.php) | todo | — | [detail](#date-and-inline-arrive-with-the-database-notifications-panel) |
| `view()` / `viewData()` / `safeViews()` | [`safeViews()` / `isViewSafe()`](../../../vendor/filament/notifications/src/Notification.php) | todo | — | [detail](#custom-views-and-their-safe-view-gate-are-unported) |
| `broadcast()` / `toBroadcast()` / `getBroadcastMessage()` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | blocked | the broadcast delivery arm | [detail](#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| `sendToDatabase()` / `toDatabase()` / `getDatabaseMessage()` / `fromDatabase()` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | blocked | notification storage | [detail](#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| `assertNotified()` / `assertNotNotified()` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | todo | — | [detail](#the-assertion-helpers-read-a-collection-the-stack-does-not-hold) |
| `Arrayable` / `HasEmbeddedView` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) (the `implements` list) | skipped | — | [diverges](#the-laravel-array-and-embedded-view-contracts-have-no-counterpart) |

## Details

### The toast markup is missing the date and inline arms

`toEmbeddedHtml()` renders the icon, the title, the body, the actions and the close button, which the port renders too, plus two things it does not: a `<time class="fi-no-notification-date">` element when `getDate()` is filled, and an `fi-inline` class that switches the whole toast from a floating card to a list row (the database-notifications modal renders its stored notifications inline).
Both are the markup half of the `date()` and `inline()` rows and land with them; the stylesheet mirrors the same two gaps, carrying no `.sy-no-notification-date` rule and no `&:not(.sy-inline)` split.
The notification **id**, which Filament's root carries in `wire:key`, in the `close-notification` listener and in the Alpine data object, is the third, tracked by [its own **todo** row](#closing-a-toast-by-id-needs-the-id-in-the-markup).
The actions container renders, and it carries a rule of its own: [why it is not the `sy-ac` Filament would reuse](#the-actions-row-is-styled-by-its-own-class-where-filament-reuses-the-cross-package-fi-ac).

The `.sy-no-notification*` rules port `notification.css` selector for selector and declaration for declaration, under the `fi-*` → `sy-*` class rename and the `--color-*` / `--gray-*` → `--sy-*` custom-property rename, with three edits the port's own shape forces.
Filament's `&:not(.fi-inline)` block is folded into the base rule, because [`inline()` is unported](#date-and-inline-arrive-with-the-database-notifications-panel) and a two-arm split with one arm is just the base.
The base rule drops `invisible` and spells its `transition` out per property, because nothing here toggles visibility from JS and the enter and leave animation is the CSS of the [transition divergence](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) rather than Alpine's.
And `.sy-no-notification-actions` has no counterpart in `notification.css` at all: its declarations come from the **actions** package's [`actions.css`](../../../vendor/filament/actions/resources/css/actions.css), because [the cross-package class reuse Filament leans on is unavailable here](#the-actions-row-is-styled-by-its-own-class-where-filament-reuses-the-cross-package-fi-ac).
The fold has one consequence the merged declaration set does not show: the card chrome drops from `(0,2,0)` (`.fi-no-notification:not(.fi-inline)`) to `(0,1,0)` (`.sy-no-notification`), so a host override written at single-class specificity wins against declarations it would lose to in Filament.
The `:not(.sy-inline)` wrapper, and with it the original specificity, comes back with [`inline()`](#date-and-inline-arrive-with-the-database-notifications-panel).
The leading `.sy-no` container block in the same file ports `notifications.css`, not `notification.css`, and is [the stack's row](notifications.md).
The `.sy-closing` rule, the `@starting-style` blocks and the `prefers-reduced-motion` query are what the [transition divergence](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) puts in place of Filament's four `fi-transition-*` class hooks.

**Next step:** none on its own, because the markup arrives in the change that ports the concern behind it.

### Closing a toast by id needs the id in the markup

Filament's toast root carries `x-on:close-notification.window="if ($event.detail.id == '<id>') close()"`, and the package documents that event as public API: anything on the page dispatches `close-notification` with an `id` and the matching toast closes itself.
The port renders neither half.
The notification id reaches the markup nowhere: Filament's other two uses of it are `wire:key`, Livewire plumbing with no counterpart here, and the Alpine data object, whose id travels back out in the `notificationClosed` / `markedNotificationAsRead` / `markedNotificationAsUnread` events, the first [skipped because the stack holds nothing to forget](notifications.md#a-toast-closes-client-side-and-is-never-re-rendered), the other two [deferred with the database panel](#markasread-and-markasunread-belong-to-the-database-panel).
The [toast controller](../../../packages/notifications/assets/src/controllers/notification_controller.ts) listens for no window event either.

Nothing waits on this, which is why it is **todo** rather than **blocked**: the id is on the object (`getId()`), the event is a plain `CustomEvent`, and `close()` is the method the close button calls.

**Next step:** emit the id as a Stimulus value on the toast root and add a `sylament:close-notification@window` action that closes when the detail id matches.
The event name takes the [`sylament:` prefix](../translations.md#names-with-no-filament-source) the delivery event uses, so the pair reads as one namespace.

### `markAsRead()` and `markAsUnread()` belong to the database panel

Filament's Alpine `notificationComponent` carries five behaviours beyond the transition and FLIP machinery that the [transition divergence](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) replaces wholesale.
Three are ported by [`notification_controller.ts`](../../../packages/notifications/assets/src/controllers/notification_controller.ts): the auto-dismiss timer, its hover-pause (at expiry over a hovered toast, wait for `mouseleave`), and `close()`.
The other two, `markAsRead()` and `markAsUnread()`, dispatch `markedNotificationAsRead` / `markedNotificationAsUnread` window events that only the `DatabaseNotifications` Livewire component listens for, and nothing in the port listens for them.

**Next step:** port both with the database-notifications panel, in the same change that gives them a listener.

### `date()` and `inline()` arrive with the database-notifications panel

Both concerns are two-method value holders (`date()`/`getDate()`, `inline()`/`isInline()`) with no dependency of their own, so neither is blocked on anything: they are unported because their only consumer is.
Filament sets the date in `DatabaseNotifications::getNotification()` (`$date->diffForHumans()` off the stored `created_at`) and calls `inline()` on the notifications the same modal renders; a toast stack shows neither.
`isInline` also gates the assertive-role split: an inline notification never gets `role="alert"`, or opening the panel would replay every stored danger notification as an assertive burst.

**Next step:** port both, their two markup arms and their two stylesheet arms with the database-notifications panel, and carry the `isInline` gate into the [`role` expression](#the-assertive-split-is-per-toast-and-the-container-stays-polite) in the same change.

### Custom views and their safe-view gate are unported

`view()` / `viewData()` (support-level `ViewComponent` members) let a notification render an application-supplied Blade view instead of the built-in toast, and `safeViews()` + `isViewSafe()` are the security gate around it: `fromArray()` accepts a `view` key from stored data **only** if the class has whitelisted it, so stored data cannot inject an arbitrary view.
The port's `toArray()` writes neither key and its `fromArray()` reads neither.

**Next step:** port the pair and the gate together, because the whitelist is the reason reading a `view` key off session or database data is safe at all.
Sylament's [`ViewComponent`](../support/view-component.md) carries `template()`/`templateData()`, so the work is the `safeViews()` allow-list plus the two array keys.

### The broadcast and database delivery arms are deferred whole

The members split across the two out-of-request deliveries: `broadcast()`/`toBroadcast()`/`getBroadcastMessage()` push a Laravel `BroadcastMessage` at each notifiable user, and `sendToDatabase()`/`toDatabase()`/`getDatabaseMessage()`/`fromDatabase()` write and read Laravel's `notifications` table (`getDatabaseMessage()` forces `duration: 'persistent'`, tags `format: 'filament'` and drops the id; `fromDatabase()` restores it from the row key).
Both lean on Laravel's notification infrastructure (`Illuminate\Notifications\Notification`, the `notify()` trait on the user model, Echo), none of which has a Symfony counterpart in the port.

**Next step:** each waits on infrastructure the port does not have.
The broadcast arm's Symfony shape is Mercure, the database arm's is a Doctrine entity plus the panel that reads it, and each carries its own delivery class (`BroadcastNotification`, `DatabaseNotification`) and its own **blocked** row in the [package README](README.md#unported).

### The assertion helpers read a collection the stack does not hold

`Notification::assertNotified()` / `assertNotNotified()` mount Filament's `Notifications` Livewire component and assert against its `notifications` collection; `Testing/TestsNotifications` wraps both statics for Livewire's `Testable`, and `Testing/helpers.php` wraps `assertNotified()` as a Pest function.
Here the stack [holds no collection](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported): the equivalent read is [`SessionNotifications::pull()`](../../../packages/notifications/src/Delivery/SessionNotifications.php), which the shipped tests assert against directly.

**Next step:** port as a Pest-shaped helper over `SessionNotifications`, not as a Livewire `Testable` mixin, because the mixin registration is [skipped for the same reason tables' is](notifications-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported).
The two `src/Testing/` files keep their **todo** rows in the [package README](README.md#unported) until then.

## Semantics

### The assertive split is per toast, and the container stays polite

The stack container is a single `role="status"` live region (`aria-atomic="false"`, so a screen reader announces only what was added).
A **danger** toast overrides that with `role="alert"` on itself, which is assertive and interrupts.
That split is Filament's, and the reason it is per toast rather than per stack: an error has to interrupt, everything else must not.
When [`inline()`](#date-and-inline-arrive-with-the-database-notifications-panel) lands, the override gains Filament's second condition: an inline notification never gets it, so opening a list of stored notifications does not replay every past error assertively.

### The default sanitizer is stricter than the one Filament tunes

`str($title)->sanitizeHtml()` **is** `symfony/html-sanitizer`: Filament's macro resolves Symfony's `HtmlSanitizerInterface`, so the engine on both sides is the same one, reached here through the twig-bridge [`sanitize_html`](../../../packages/notifications/templates/partials/Notification/index.html.twig) filter (declared `is_safe: ['html']`, wired by FrameworkBundle and TwigBundle once the component is installed, with no consumer config).
What differs is the **configuration**.
Sylament renders through Symfony's `default` sanitizer, which allows safe elements only.
[Filament tunes theirs further](../../../vendor/filament/support/src/SupportServiceProvider.php): `class` and `style` on every element, six **named** data attributes and no wildcard (`data-color`, `data-cols`, `data-col-span`, `data-from-breakpoint`, `data-id`, `data-type`), `width`/`height` on `img` only, relative links and media, and `withMaxInputLength(500000)`, the settings a rich-text editor's output needs.

So markup that survives in Filament (a styled `<span class="…">` in a body) is stripped of its attributes here.
A consumer who wants Filament's latitude reconfigures `framework.html_sanitizer`; a sylament-tuned **named** sanitizer is the evolution path if the toolkit ever ships rich content of its own.
Nothing about this is a divergence in the port (the same library, a different default config), but it is the one thing a reader would get wrong from the template alone.

### `iconSize()` does not survive the delivery hop

`toArray()` writes no size key and `fromArray()` never calls the setter, and both delivery paths (the session flash bag and the live round trip) rebuild the toast through that pair, so a **sent** notification renders its icon at the template's default size whatever `iconSize()` was set to; only a directly rendered instance (`render($twig)` on the live object) honours it.
This is Filament parity, not a port gap: their [`toArray()` / `fromArray()`](../../../vendor/filament/notifications/src/Notification.php) carry no size key either, and their delivered toasts rebuild through the same pair.
Carrying the size across would be a Sylament-only extension, declined while the surface stays at parity.

### Toast actions are re-viewed as small links, and the `actions` key restores them

[`getActions()`](../../../packages/notifications/src/Concerns/HasActions.php) re-views every entry at read time: `defaultTemplate(Action::LINK_TEMPLATE)` on an action, `defaultTriggerTemplate(ActionGroup::LINK_TEMPLATE)` on a group, `defaultSize(Size::Small)` on both, and a fourth call on the group alone that [Filament does not make](#the-re-view-defaults-a-group-to-teleport-where-filaments-toast-groups-clip).
They are **defaults**, not settings, so an action that called `template()` or `size()` itself keeps what it chose; only an unconfigured one becomes a small link.
That is Filament's [`defaultView` / `defaultTriggerView`](../../../vendor/filament/notifications/src/Concerns/HasActions.php) pair with the [Blade view → Twig template](../translations.md#idiom) translation applied, and it runs on the way **out** as well: `toArray()` serializes the re-viewed action, so the template and size cross the hop as literal values rather than being re-derived.

Coming back, `fromArray()` tells a group from an action by the presence of an `actions` key on the entry, the same discriminator [`ActionGroup::fromArray()`](../../../packages/actions/src/ActionGroup.php) uses for its own children, and it is exact because `Action::toArray()` never writes that key and `ActionGroup::toArray()` always does.
Nesting therefore restores to any depth without a type tag in the payload.

### Toast actions cannot open modals at Filament parity

Toast actions dispatch events and open URLs, and they do **not** mount modals, which is parity rather than a shortfall.
[`Action::toArray()`](../../../vendor/filament/actions/src/Action.php) serializes no modal configuration and no closures, and Filament's `Notifications` Livewire component implements no actions-host contract, so a Filament toast action cannot mount a modal either.
The obvious reading of "actions on a notification" is that a toast can ask for confirmation, and neither codebase can.

## Divergences

### `make()` mints the id itself, because the resolver takes no constructor arguments

Filament seals `Notification::__construct(string $id)` and fills it through `app(static::class, ['id' => $id ?? Str::orderedUuid()])`.
Sylament's `make()` resolves the instance first, through [`Component::newInstance()`](../support/component.md#newinstance-routes-every-make-through-a-locator) and its class-keyed service locator, which cannot pass constructor arguments, then calls the `id()` setter with `$id ?? Uuid::v7()->toRfc4122()`.
`symfony/uid`'s v7 is time-ordered like `Str::orderedUuid()`, which is a timestamp-first COMB in the v4 layout rather than a v7.
The class declares no constructor, so a user subclass can declare constructor dependencies of its own.

This is the global [`make()` arguments move from constructors to setters](../support/component.md#make-arguments-move-from-constructors-to-setters) rule applied here, and it is invisible at the call site: `Notification::make()` and `Notification::make('my-id')` both behave as they do in Filament.

### `fromArray()` drops a value it cannot type, where Filament's typed setters throw

Every key comes back through a type check: a non-string `title`, `body` or `status` becomes `null`, a `duration` that is neither `int` nor `string` falls back to the default, and a `color`/`iconColor` array is accepted only when **every** shade in it is a string, so one non-string entry drops the whole map.

Filament's `fromArray()` assigns each key straight into the setter, and those setters are typed unions, not `mixed`: [`title(string | Closure | null)`](../../../vendor/filament/notifications/src/Concerns/HasTitle.php), [`body(...)`](../../../vendor/filament/notifications/src/Concerns/HasBody.php), [`status(...)`](../../../vendor/filament/notifications/src/Concerns/HasStatus.php), [`duration(int | string | Closure | null)`](../../../vendor/filament/notifications/src/Concerns/HasDuration.php).
So a junk value out of the session is an uncaught `TypeError` there, and the port is the **tolerant** arm rather than the strict one: the notification still renders, minus the field that arrived malformed.

Tolerant is the deliberate reading here, and it does not soften the house fail-fast rule, which is about **our own callers'** mistakes, while this is a deserializer reading input it does not control.
The values cross a session boundary, so they are attacker-adjacent by the time `fromArray()` sees them, and the reader is a *delivery* path: one malformed entry throwing would take down the page that was trying to show the user a message, and take the other notifications in the bag with it.
A field that does not type is a data fact, not a bug, so it is dropped and the rest of the notification is delivered.
Both arms are pinned by the junk-type and array-shade cases in [`NotificationTest`](../../../packages/notifications/tests/Unit/NotificationTest.php).

### A `ScalableIcon` crosses the session unflattened and resolves at render size

Filament's `toArray()` resolves a scalable icon at serialization time (`$icon instanceof ScalableIcon` takes `getIconForSize(IconSize::Large)`, a hardcoded size chosen before anyone knows how the toast will render it), and only a plain `BackedEnum` falls through to `->value`.
The port inverts the order of the same two arms: a `BackedEnum` that implements [`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) is **kept as the enum** (the session PHP-serializes enums; `fromArray()` admits a `BackedEnum` back through `icon()`), and only a plain enum flattens to its value.
Resolution then happens where every other icon resolves, in [`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php)'s `ScalableIcon` → `BackedEnum` cascade at the size the template passes, so a scalable icon is size-correct on the delivery path where Filament's is pinned to `Large` whatever renders it.
The round trip is pinned by the `ScalableFixtureIcon` case in [`NotificationTest`](../../../packages/notifications/tests/Unit/NotificationTest.php).

### The `actions` key is a list on both sides of the hop

Filament's `array_map` preserves whatever keys `getActions()` returned, on both `toArray()` and `fromArray()`, so `actions(['undo' => $action])` serializes to `['undo' => […]]` and comes back keyed.
The port re-indexes at both ends, with `array_values()` around the serializing map and again around the restoring one, which makes the key a genuine `list<array<string, mixed>>` (the shape the `toArray()` docblock declares) and makes the round trip idempotent: a keyed input would otherwise serialize keyed, restore re-indexed, and serialize differently the second time.
Nothing reads those keys, since the toast renders the actions in order, so the only thing given up is a label the payload never used.
Pinned by the keyed round-trip case in [`NotificationTest`](../../../packages/notifications/tests/Unit/NotificationTest.php).

The restoring map also skips a non-array entry rather than passing it into `Action::fromArray()`, and drops any non-string key inside an entry it keeps: the same [tolerant-deserializer stance](#fromarray-drops-a-value-it-cannot-type-where-filaments-typed-setters-throw) every other key here takes, applied to a value that crossed the session boundary.

### `send()` always writes the flash bag, and the live arm decides late

Filament's `send()` pushes onto the Laravel session under `filament.notifications`; a Livewire `on('dehydrate')` hook then decides, per request, whether to hand the pending notifications to the live stack.
Sylament's `send()` does the same one thing, pushing `toArray()` onto the flash bag under [`sylament.notifications`](../../../packages/notifications/src/Delivery/SessionNotifications.php), with no branching at send time either.
What changes is everything downstream of it, recorded where the machinery lives: the [`PreRenderEvent` subscriber](notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) that replaces the dehydrate hook, and the [browser event carrying rendered HTML](notifications-bundle.md#there-is-no-claimed-notifications-key-because-the-toast-travels-in-the-same-response) that replaces Livewire's re-request.

Two consequences belong to `send()` itself.
It needs the container-built instance: `SessionNotifications` reaches the object through the `#[Required]` `setSessionNotifications()` setter, so `send()` on a `new Notification()` throws a `LogicException` naming the fix rather than silently dropping the notification (pinned by [`SendTest`](../../../packages/notifications/tests/Integration/SendTest.php)).
And an out-of-request `send()` (console command, worker) fails fast on the missing session: a toast is a browser artifact, and the delivery arms that work without a browser are the database and broadcast ones, both deferred above.

### The status fallbacks for icon and icon colour live in the template

Filament overrides two support concerns on the notification: `Concerns\HasIcon` aliases the base getter and falls back by status to `FilamentIcon::resolve(NotificationsIconAlias::NOTIFICATION_*) ?? Heroicon::Outlined*`, and `Concerns\HasIconColor` falls back to the status string.
Sylament composes support's [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) and [`HasIconColor`](../../../packages/support/src/Components/Concerns/HasIconColor.php) unchanged and resolves both fallbacks in [the toast template](../../../packages/notifications/templates/partials/Notification/index.html.twig) instead: a `statusIcons` map keyed by status, the matching [`NotificationsIconAlias`](notifications-icon-alias.md) constant passed as the alias argument (only when the user set no icon of their own, so an explicit icon is never re-aliased), and `component.getIconColor ?? status` for the colour.

The shape is the one tables ships: its [table partial](../../../packages/tables/templates/partials/Table/index.html.twig) writes the empty-state icon as `:icon="emptyStateIcon ?? 'heroicons:x-mark'"` with `:alias="emptyStateIcon ? null : constant('…::EMPTY_STATE')"`, which is this template's expression with one status instead of four.
The rendered result is identical and the setters are untouched: `icon()` and `iconColor()` take the same arguments as Filament's.

The **getters** are where it shows, and it is a real API difference rather than an invisible one.
Filament's `Notification::getIcon()` / `getIconColor()` return the status fallback (`Heroicon::OutlinedCheckCircle`, `'success'`); these return only what the user set, so a status-only notification answers `null` from both, and `toArray()` therefore carries `null` in the `icon` and `iconColor` keys where Filament's carries the resolved icon and the status string.
Nothing here reads either: the template resolves the chain itself, and `fromArray()` re-derives it the same way on the far side of the session.
Reversal condition: a **PHP** consumer that needs the resolved icon (the database panel is the plausible one) moves the chain onto the class, where the template then reads it as one getter.

### A closure duration evaluating to `null` throws, where Filament falls back to persistent

Filament's `getDuration()` ends `?? 'persistent'`.
That fallback is unreachable for a literal, because the property is typed `int|string|Closure` and `duration(null)` already stores `'persistent'`, so it fires in one case only: when a **closure** duration evaluates to `null`.
Sylament's getter returns `$this->evaluate($this->duration)` against an `int|string` return type, so that same case is a `TypeError` at the boundary rather than a silent persistent toast, pinned by [`HasDurationTest`](../../../packages/notifications/tests/Unit/Concerns/HasDurationTest.php).
`duration(null)` still means persistent, because the **setter** coerces it, exactly as Filament's does.

Fail-fast over a silent fallback, per the house rule: a closure that returns `null` by accident produces a toast that never leaves the screen, and the type error names the callback that did it.

### The toast trades Alpine's transition classes for CSS and a Stimulus controller

Filament animates a toast with four `x-transition:*` class hooks driven by Alpine, and re-flows the stack with a `Livewire.interceptMessage` FLIP: measure each toast's `top` before the morph, measure again after, and play the difference back through the Web Animations API, with a `prefers-reduced-motion` early return, because `element.animate()` is not covered by a CSS reduced-motion reset.

Sylament's toast enters with `@starting-style` (which covers a page-load render and an appended one identically, the same mechanism the action modals use) and leaves by animating its own height to zero before removing itself, so siblings reflow through the same CSS transition; `prefers-reduced-motion` is one media query.
The [controller](../../../packages/notifications/assets/src/controllers/notification_controller.ts) adds a `transitionend` listener plus a 400 ms `setTimeout` fallback so a toast is removed even when the transition never fires.
Two cases reach the fallback: a hidden tab, and `prefers-reduced-motion`, where the media query sets `transition: none` so the exit transition never starts and therefore never ends.
Under reduced motion the toast still leaves, 400 ms after the click, without animating.

The FLIP is not reproducible here and does not need to be: it exists because Filament's stack is re-rendered by the server, so a removed toast's siblings jump between morphs.
Here removal is client-owned (the element takes itself out of the DOM and nothing re-renders behind it), which is the same property that makes the [zombie-toast class](notifications.md#a-toast-closes-client-side-and-is-never-re-rendered) impossible.

### The actions row is styled by its own class, where Filament reuses the cross-package `fi-ac`

Filament writes one row-layout rule, `.fi-ac` in [`actions/…/actions.css`](../../../vendor/filament/actions/resources/css/actions.css), and reuses the class for **every** actions row it renders, the toast's (`<div class="fi-ac fi-no-notification-actions">`) included.
That reuse is not available here, because the `fi-ac` → `sy-ac` translation names a different thing: bare `sy-ac` is the *action trigger* marker every `templates/partials/Action/*` root carries, and the row container takes the schemas-namespaced `sy-sc-actions-ctn` beside the component that emits it ([schemas/actions.md](../schemas/actions.md#the-actions-row-container-class-is-sy-sc-actions-ctn-not-a-fi-ac-translation), [schemas/styles.md](../schemas/styles.md)).

A toast row carrying `sy-ac` would therefore paint nothing, and its buttons would fall back to inline flow with no `gap-3`, no `flex-wrap` and no `items-center`.
The row has its own rule instead, `.sy-no-notification-actions { @apply flex flex-wrap items-center gap-3; }` in [`notification.css`](../../../packages/notifications/assets/src/styles/components/notification.css), the declarations vendor's `.fi-ac` and its `:not(.fi-width-full)` arm resolve to for this element, and the template does not put `sy-ac` on the container, since the container is not a trigger.
Reusing `sy-sc-actions-ctn` is the alternative and is worse: it would make a notifications template depend on a schemas class for its layout.

The same applies to any future port of Filament's `database-notifications.blade.php` row, which is `<div class="fi-ac">` with no second class to hang a rule on: that one needs a class of its own before it can be styled.

### The re-view defaults a group to teleport, where Filament's toast groups clip

[`getActions()`](../../../packages/notifications/src/Concerns/HasActions.php) adds `->defaultDropdownTeleport()` to the group arm of its re-view.
Filament's [`HasActions`](../../../vendor/filament/notifications/src/Concerns/HasActions.php) stamps only the trigger view and the size, so a Filament `ActionGroup` on a toast opens its panel into `fi-no-notification`'s `overflow-hidden` and is cut off at the card's edge; the user has to know to call `->dropdownTeleport()` themselves.
Both codebases have the clipping ancestor and both have the escape hatch; the divergence is only which way the default points.

It points this way because the toast is the one container whose clipping is *certain*: the notification card is `overflow-hidden` unconditionally, in [our stylesheet](../../../packages/notifications/assets/src/styles/components/notification.css) and in Filament's, so every group placed there is clipped and no reasonable composition avoids it.
A default that is right in every case it fires is worth more than the parity, and it costs nothing to reverse: `defaultDropdownTeleport()` is a *default*, so `->dropdownTeleport(false)` on the group still wins, resolved by the [`??` precedence](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase) that keeps an explicit `false` from falling through to it.

The stamp runs before serialization, so what crosses the flash bag is the **resolved** flag: `toArray()` writes `hasDropdownTeleport: true` and the rebuilt group comes back explicitly teleported rather than relying on the receiving side to re-apply the default.
That is the same read-time-then-serialize order the template and size take.

### The toast root owns the click, and the dispatch arm carries only payload

Filament wires each toast action's behaviour onto the trigger itself: [`getAlpineClickHandler()`](../../../vendor/filament/actions/src/Action.php) emits one `x-on:click` per action (`$dispatch(…)`, or `close()` when the action closes), so the handler and its data sit on the same element.
Sylament splits them on the arm a toast action takes: a dispatching action's trigger carries the `data-sylament-action-*` payload attributes and no handler ([can-dispatch-event.md](../actions/can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding), which also lists every arm of the trigger's click wiring), and the toast root carries the one delegated click action, written by the same `stimulus_controller(…)|stimulus_action(…)` hash that carries the duration value, so the root keeps exactly one `data-action` writer.

Delegation follows from where the trigger templates ship.
An action renders inside modals, tables and schemas as well as inside a toast, and a Stimulus action attribute names its controller at write time, so the trigger cannot know which controller surrounds it, whereas a `data-sylament-action-*` attribute means the same thing everywhere and the surrounding context decides what to do with it.
It also puts the handler on the element that **is** the toast controller's scope, so `close()` needs no lookup, and it leaves the close button alone: that `IconButton` carries no payload attribute, so a delegated click cannot double-fire it.

The receiving half is [`handleActionClick()`](../../../packages/notifications/assets/src/controllers/notification_controller.ts), and it handles **only the close**.
It walks `closest('[data-sylament-action-close]')` from `event.target` rather than reading `currentTarget`, which on a delegated action is always the root.
It then re-checks `this.element.contains(trigger)`, because `closest()` does not stop at the toast: a click on the toast's own chrome would otherwise walk past the root and claim a close marker on some enclosing element.
Then it calls `close()`, synchronously.

The event itself is delivered by the page-wide capture-phase document listener actions ships: [actions-bundle.md](../actions/actions-bundle.md#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).
That listener reads the trigger's payload in the capture phase, so for `dispatch()->close()` the payload is read before this bubble-phase handler starts closing the toast.
A toast placed outside every live component gives a `dispatchSelf()` trigger no `live` ancestor, so its delivery falls through to the plain-dispatch arm.
The close-only arm (`close()` with no event) also carries `data-action="sylament--support--modal#dismiss"`, harmless in a toast because no `sylament--support--modal` controller is an ancestor there, so Stimulus binds it to nothing while the root's delegation still reads the close marker.

The **mount** arm is the trap to know when building against this.
A bare `Action::make('x')` (no `url()`, no event, no `close()`) falls through to it and emits `live#action` with `action: mountAction`, which is not inert: inside a live component's subtree Stimulus binds it, and a click fires a real round trip against the **surrounding** component, which is not the toast and knows nothing about the action.
It is only silent when no `live` controller is an ancestor.

Which arm a delivered toast action lands on is decided by what survives the hop.
The port's `Action::toArray()` carries no client click handler and no parent-action handler, so a restored action can only reach the url arm, the dispatch arm, the close-only arm or, given none of those, the mount arm.
A toast action is therefore written with `url()`, `dispatch()` or `close()`, and a bare one is a mistake the markup does not announce.

### The Laravel array and embedded-view contracts have no counterpart

Filament's class declares `implements Arrayable, HasEmbeddedView`.
Neither is ported.
`Illuminate\Contracts\Support\Arrayable` is a Laravel marker for `toArray()`, which PHP needs no interface to call and Symfony ships no equivalent of; `HasEmbeddedView` marks a component that renders through `toEmbeddedHtml()` instead of a view file, and Sylament's rendering seam is `ViewComponent::render(Environment $twig)` against a Twig template, the same Blade → Twig translation recorded in [translations.md](../translations.md#idiom).
`toArray()` itself ships as a plain method, and it is what crosses the session boundary either way.
