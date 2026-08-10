# Notification (notifications)

[`Sylament\Notifications\Notification`](../../../packages/notifications/src/Notification.php)
— the notification builder: `Notification::make()->title('Saved')->success()->send()`.
It ports Filament's
[`Filament\Notifications\Notification`](../../../vendor/filament/notifications/src/Notification.php),
a `ViewComponent` subclass that sits at `src/` root the way actions'
[`Action`](../actions/action.md) does, and it is the only class the toast surface
exposes to application code.

The five ported concerns carry no rows of their own upstairs: each has exactly
one consumer, so they are folded into the table below per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). Filament's
toast markup lives in `toEmbeddedHtml()` rather than in a Blade view, so its port
— [`partials/Notification/index.html.twig`](../../../packages/notifications/templates/partials/Notification/index.html.twig)
— is folded here too, together with the toast stylesheet and the toast Stimulus
controller, which port `resources/css/notification.css` and the Alpine
`notificationComponent` in `resources/js/components/notification.js`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`notifications/src/Notification.php`](../../../vendor/filament/notifications/src/Notification.php)
  — the builder, `toArray()`/`fromArray()`, the four delivery methods, the test
  assertions and `toEmbeddedHtml()`
- [`notifications/src/Concerns/`](../../../vendor/filament/notifications/src/Concerns)
  — the ten concerns behind the rows below
- [`notifications/resources/css/notification.css`](../../../vendor/filament/notifications/resources/css/notification.css)
  and [`resources/js/components/notification.js`](../../../vendor/filament/notifications/resources/js/components/notification.js)
  — the toast's styles and its client behaviour

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries any of three: **detail** → the write-up in [Details](#details),
deleted once the row is **done**; **semantics** → the permanent note in
[Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` + the uuid id (`HasId`)](../../../packages/notifications/src/Concerns/HasId.php) | [`make()` / `__construct()`](../../../vendor/filament/notifications/src/Notification.php), [`Concerns/HasId.php`](../../../vendor/filament/notifications/src/Concerns/HasId.php) | done | — | [diverges](#make-mints-the-id-itself-because-the-resolver-takes-no-constructor-arguments) |
| [`title()` (`HasTitle`)](../../../packages/notifications/src/Concerns/HasTitle.php) | [`Concerns/HasTitle.php`](../../../vendor/filament/notifications/src/Concerns/HasTitle.php) | done | — | — |
| [`body()` (`HasBody`)](../../../packages/notifications/src/Concerns/HasBody.php) | [`Concerns/HasBody.php`](../../../vendor/filament/notifications/src/Concerns/HasBody.php) | done | — | — |
| [`status()` + `success()`/`info()`/`warning()`/`danger()` (`HasStatus`)](../../../packages/notifications/src/Concerns/HasStatus.php) | [`Concerns/HasStatus.php`](../../../vendor/filament/notifications/src/Concerns/HasStatus.php) | done | — | — |
| [`duration()` / `seconds()` / `persistent()` (`HasDuration`)](../../../packages/notifications/src/Concerns/HasDuration.php) | [`Concerns/HasDuration.php`](../../../vendor/filament/notifications/src/Concerns/HasDuration.php) | done | — | [diverges](#a-closure-duration-evaluating-to-null-throws-where-filament-falls-back-to-persistent) |
| [`icon()` / `iconSize()` + the status icon fallback](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`Concerns/HasIcon.php`](../../../vendor/filament/notifications/src/Concerns/HasIcon.php), support's `HasIcon`/`HasIconSize` | done | — | [diverges](#the-status-fallbacks-for-icon-and-icon-colour-live-in-the-template) |
| [`iconColor()` + the status colour fallback](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`Concerns/HasIconColor.php`](../../../vendor/filament/notifications/src/Concerns/HasIconColor.php), support's `HasIconColor` | done | — | [diverges](#the-status-fallbacks-for-icon-and-icon-colour-live-in-the-template) |
| [`color()` (support `HasColor`)](../../../packages/notifications/src/Notification.php) | [`Filament\Support\Concerns\HasColor`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`toArray()` / `fromArray()` — the eight ported keys](../../../packages/notifications/src/Notification.php) | [`toArray()` / `fromArray()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [diverges](#fromarray-drops-a-value-it-cannot-type-where-filaments-typed-setters-throw) · [diverges](#a-scalableicon-crosses-the-session-unflattened-and-resolves-at-render-size) |
| [`send()` → the session flash bag](../../../packages/notifications/src/Notification.php) | [`send()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [diverges](#send-always-writes-the-flash-bag-and-the-live-arm-decides-late) · [semantics](#a-send-during-the-acting-components-own-render-arrives-on-the-next-page-load) |
| [The toast markup — chrome, icon, title, body, close button](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/notifications/src/Notification.php) | in progress | — | [detail](#the-toast-markup-is-missing-the-date-and-inline-arms) |
| [`role="alert"` on danger, polite otherwise](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [semantics](#the-assertive-split-is-per-toast-and-the-container-stays-polite) |
| [Title / body sanitization (`sanitize_html`)](../../../packages/notifications/templates/partials/Notification/index.html.twig) | [`str(...)->sanitizeHtml()`](../../../vendor/filament/notifications/src/Notification.php) | done | — | [semantics](#the-default-sanitizer-is-stricter-than-the-one-filament-tunes) |
| [Toast styles (`sy-no-notification*`)](../../../packages/notifications/assets/src/styles/components/notification.css) | [`resources/css/notification.css`](../../../vendor/filament/notifications/resources/css/notification.css) | in progress | — | [detail](#the-toast-markup-is-missing-the-date-and-inline-arms) · [diverges](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) |
| [Toast controller — auto-dismiss, hover-pause, close](../../../packages/notifications/assets/src/controllers/notification_controller.ts) | [`resources/js/components/notification.js`](../../../vendor/filament/notifications/resources/js/components/notification.js) | in progress | the database-notifications panel | [detail](#markasread-and-markasunread-belong-to-the-database-panel) · [diverges](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller) |
| `close-notification` — closing a toast by id from a browser event | [`x-on:close-notification.window`](../../../vendor/filament/notifications/src/Notification.php) | todo | — | [detail](#closing-a-toast-by-id-needs-the-id-in-the-markup) |
| `actions()` (`HasActions`) | [`Concerns/HasActions.php`](../../../vendor/filament/notifications/src/Concerns/HasActions.php), the `actions` key of `toArray()`/`fromArray()` | blocked | `Action::toArray()` / `fromArray()` | [detail](#actions-on-a-notification-wait-on-actiontoarray) · [semantics](#toast-actions-cannot-open-modals-at-filament-parity) |
| `date()` (`HasDate`) | [`Concerns/HasDate.php`](../../../vendor/filament/notifications/src/Concerns/HasDate.php) | todo | — | [detail](#date-and-inline-arrive-with-the-database-notifications-panel) |
| `inline()` (`CanBeInline`) | [`Concerns/CanBeInline.php`](../../../vendor/filament/notifications/src/Concerns/CanBeInline.php) | todo | — | [detail](#date-and-inline-arrive-with-the-database-notifications-panel) |
| `view()` / `viewData()` / `safeViews()` | [`safeViews()` / `isViewSafe()`](../../../vendor/filament/notifications/src/Notification.php) | todo | — | [detail](#custom-views-and-their-safe-view-gate-are-unported) |
| `broadcast()` / `toBroadcast()` / `getBroadcastMessage()` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | blocked | the broadcast delivery arm | [detail](#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| `sendToDatabase()` / `toDatabase()` / `getDatabaseMessage()` / `fromDatabase()` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | blocked | notification storage | [detail](#the-broadcast-and-database-delivery-arms-are-deferred-whole) |
| `assertNotified()` / `assertNotNotified()` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) | todo | — | [detail](#the-assertion-helpers-read-a-collection-that-no-longer-exists) |
| `Arrayable` / `HasEmbeddedView` | [`Notification.php`](../../../vendor/filament/notifications/src/Notification.php) (the `implements` list) | skipped | — | [diverges](#the-laravel-array-and-embedded-view-contracts-have-no-counterpart) |

The **done** rows are covered by five suites:
[`NotificationTest`](../../../packages/notifications/tests/Unit/NotificationTest.php)
(the minted uuid, the eight-key `toArray()` shape, the `fromArray()` round trip,
the junk-type and array-shade arms, closure evaluation at read time),
[`HasDurationTest`](../../../packages/notifications/tests/Unit/Concerns/HasDurationTest.php)
and
[`HasStatusTest`](../../../packages/notifications/tests/Unit/Concerns/HasStatusTest.php)
(the concern behaviour, including the null-duration throw below),
[`NotificationRenderTest`](../../../packages/notifications/tests/Integration/NotificationRenderTest.php)
(the rendered toast: status class, sanitized title with the `<script>` stripped,
`role="alert"` on danger and its absence otherwise, the status icon fallback and
an explicit icon overriding it, the duration value on the controller, the colour
policy's classes, the labelled close button) and
[`SendTest`](../../../packages/notifications/tests/Integration/SendTest.php)
(`send()` writing the flash, and the fail-fast on an instance built outside the
container). The unported rows have no coverage because they have no code.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence or semantics note it earned stay behind.

### The toast markup is missing the date and inline arms

`toEmbeddedHtml()` renders four things the port renders too — the icon, the title,
the body and the close button — plus two it does not: a
`<time class="fi-no-notification-date">` element when `getDate()` is filled, and
an `fi-inline` class that switches the whole toast from a floating card to a list
row (the database-notifications modal renders its stored notifications inline).
Both are the markup half of the `date()` and `inline()` rows below and land with
them; the stylesheet mirrors the same two gaps, carrying no
`.sy-no-notification-date` rule and no `&:not(.sy-inline)` split. The actions
container is the third gap and is tracked by its own **blocked** row, and the
notification **id** — which Filament's root carries three times over, in
`wire:key`, in the `close-notification` listener and in the Alpine data object —
is the fourth, tracked by
[its own **todo** row](#closing-a-toast-by-id-needs-the-id-in-the-markup).

Scoping the stylesheet claim, since one file carries three things. The
`.sy-no-notification*` rules port `notification.css` selector for selector and
declaration for declaration, under the `fi-*` → `sy-*` class rename and the
`--color-*` / `--gray-*` → `--sy-*` custom-property rename, with two edits the
port's own shape forces: Filament's `&:not(.fi-inline)` block is folded into the
base rule, because [`inline()` is unported](#date-and-inline-arrive-with-the-database-notifications-panel)
and a two-arm split with one arm is just the base; and the base rule drops
`invisible` and spells its `transition` out per property, because nothing here
toggles visibility from JS and the enter/leave animation is the CSS below rather
than Alpine's. The leading `.sy-no` container block in the same file ports no part
of `notification.css` — it ports `notifications.css` and is
[the stack's row](notifications.md), **done** there. And the `.sy-closing` rule,
the `@starting-style` blocks and the `prefers-reduced-motion` query port no vendor
rule at all: they are what the
[transition divergence](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller)
puts in place of Filament's four `fi-transition-*` class hooks.

**Next step:** none on its own — the markup arrives in the change that ports the
concern behind it.

### Closing a toast by id needs the id in the markup

Filament's toast root carries
`x-on:close-notification.window="if ($event.detail.id == '<id>') close()"`, and
the package documents that event as public API: anything on the page dispatches
`close-notification` with an `id` and the matching toast closes itself. The port
renders neither half. The notification id reaches the markup nowhere, and until
this row nothing here had asked for it: Filament's other two uses of it are
`wire:key`, Livewire plumbing with no counterpart here, and the Alpine data
object, whose id travels back out in the `notificationClosed` /
`markedNotificationAsRead` / `markedNotificationAsUnread` events — the first
[skipped because the stack holds nothing to forget](notifications.md#a-toast-closes-client-side-and-is-never-re-rendered),
the other two [deferred with the database panel](#markasread-and-markasunread-belong-to-the-database-panel).
The [toast controller](../../../packages/notifications/assets/src/controllers/notification_controller.ts)
listens for no window event either.

Nothing waits on this, which is why it is **todo** rather than **blocked**: the id
is already on the object (`getId()`), the event is a plain `CustomEvent`, and
`close()` is the method the close button already calls.

**Next step:** emit the id as a Stimulus value on the toast root and add a
`sylament:close-notification@window` action that closes when the detail id
matches. The event name takes the
[`sylament:` prefix](../renames.md#names-with-no-filament-source) the delivery
event already uses, so the pair reads as one namespace.

### `markAsRead()` and `markAsUnread()` belong to the database panel

Filament's Alpine `notificationComponent` carries five behaviours beyond the
transition and FLIP machinery that the
[transition divergence](#the-toast-trades-alpines-transition-classes-for-css-and-a-stimulus-controller)
replaces wholesale. Three are ported by
[`notification_controller.ts`](../../../packages/notifications/assets/src/controllers/notification_controller.ts):
the auto-dismiss timer, its hover-pause (at expiry over a hovered toast, wait for
`mouseleave`), and `close()`. The other two — `markAsRead()` and
`markAsUnread()`, which dispatch `markedNotificationAsRead` /
`markedNotificationAsUnread` window events — exist only for the
`DatabaseNotifications` Livewire component that listens for them, and there is
nothing here to read them yet.

**Next step:** port both with the database-notifications panel, in the same change
that gives them a listener.

### Actions on a notification wait on `Action::toArray()`

`Concerns/HasActions` holds `actions()` / `getActions()`, defaulting each entry to
the link view at small size, and the `actions` key of `toArray()`/`fromArray()`
serializes them through `Action::toArray()` / `ActionGroup::fromArray()`. The port
has neither half: `Sylament\Actions\Action` ships no `toArray()`/`fromArray()`
pair ([action.md](../actions/action.md) tracks it), and without one an action
cannot survive the session hop that every delivery here goes through.

The second cost is a manifest one. `sylament/notifications`
[requires no `sylament/actions`](../../../packages/notifications/composer.json)
today, deliberately: nothing in `src/` imports an actions symbol, and the
[split-repo dependency rule](../../../CLAUDE.md) declares what a package uses, not
what it may use later.

**Next step:** land `Action::toArray()`/`fromArray()`, then port the concern, the
two serialization keys, the `sy-ac` actions container in the toast template and
the `sylament/actions` require in one change. What that change does **not** buy is
modals on those actions — see the [semantics note](#toast-actions-cannot-open-modals-at-filament-parity).

### `date()` and `inline()` arrive with the database-notifications panel

Both concerns are two-method value holders (`date()`/`getDate()`,
`inline()`/`isInline()`) with no dependency of their own, so neither is blocked on
anything — they are unported because their only consumer is. Filament sets the
date from `DatabaseNotifications::getNotification()`
(`$date->diffForHumans()` off the stored `created_at`) and sets `inline` on the
notifications the same modal renders; a toast stack shows neither. `isInline` also
gates the assertive-role split — an inline notification never gets
`role="alert"`, or opening the panel would replay every stored danger notification
as an assertive burst.

**Next step:** port both, their two markup arms and their two stylesheet arms with
the panel epic, and carry the `isInline` gate into the
[`role` expression](#the-assertive-split-is-per-toast-and-the-container-stays-polite)
in the same change.

### Custom views and their safe-view gate are unported

`view()` / `viewData()` (support-level `ViewComponent` members) let a notification
render an application-supplied Blade view instead of the built-in toast, and
`safeViews()` + `isViewSafe()` are the security gate around it: `fromArray()`
accepts a `view` key from stored data **only** if the class has whitelisted it, so
stored data cannot inject an arbitrary view. The port's `fromArray()` reads
neither key.

**Next step:** port the pair and the gate together — the whitelist is not optional
polish, it is the reason reading a `view` key off session or database data is safe
at all. Sylament's [`ViewComponent`](../support/README.md) already carries
`template()`/`templateData()`, so the work is the `safeViews()` allow-list plus the
two `fromArray()` keys.

### The broadcast and database delivery arms are deferred whole

Eight members split across the two out-of-request deliveries:
`broadcast()`/`toBroadcast()`/`getBroadcastMessage()` push a Laravel
`BroadcastMessage` at each notifiable user, and
`sendToDatabase()`/`toDatabase()`/`getDatabaseMessage()`/`fromDatabase()` write and
read Laravel's `notifications` table (`getDatabaseMessage()` forces
`duration: 'persistent'`, tags `format: 'filament'` and drops the id; `fromDatabase()`
restores it from the row key). Both lean on Laravel's notification infrastructure
— `Illuminate\Notifications\Notification`, the `notify()` trait on the user model,
Echo — none of which has been mapped to a Symfony counterpart yet.

**Next step:** neither is on a milestone. The broadcast arm's Symfony shape is
Mercure, the database arm's is a Doctrine entity plus the panel that reads it, and
each carries its own delivery class (`BroadcastNotification`,
`DatabaseNotification`) and its own **blocked** row in the
[package README](README.md#components).

### The assertion helpers read a collection that no longer exists

`Notification::assertNotified()` / `assertNotNotified()` mount Filament's
`Notifications` Livewire component and assert against its `notifications`
collection; `Testing/TestsNotifications` and `Testing/helpers.php` are two thin
wrappers over exactly those two statics. Here the stack
[holds no collection](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported)
— the equivalent read is
[`SessionNotifications::pull()`](../../../packages/notifications/src/Delivery/SessionNotifications.php),
which is what the shipped tests assert against directly today.

**Next step:** port as a Pest-shaped helper over `SessionNotifications`, not as a
Livewire `Testable` mixin — the mixin registration is
[skipped for the same reason tables' is](notifications-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported).
The two `src/Testing/` files keep their **todo** rows in the
[package README](README.md#supporting-surface) until then.

## Semantics

Faithful behaviour subtle enough to be misread from the code alone. Permanent —
these stay once every row above is **done**.

### A `send()` during the acting component's own render arrives on the next page load

The live arm drains the flash on ux-twig-component's `PreRenderEvent`, ahead of the
subscriber that writes a live component's root attributes. A `send()` that runs
**after** that point — inside the acting component's own template rendering —
finds the flash already read for this response, so the toast stays in the bag and
appears on the next page load instead of the current round trip. Every legitimate
call site runs before render: live actions, `afterStateUpdated`, and the hooks
around them. The behaviour is Filament's too, arrived at from the other side —
their `on('dehydrate')` hook fires after the component has rendered, so a
render-time `send()` misses that response as well.

### The assertive split is per toast, and the container stays polite

The stack container is a single `role="status"` live region (`aria-atomic="false"`,
so a screen reader announces only what was added). A **danger** toast overrides
that with `role="alert"` on itself, which is assertive and interrupts. That split
is Filament's, and the reason it is per toast rather than per stack: an error has
to interrupt, everything else must not. When
[`inline()`](#date-and-inline-arrive-with-the-database-notifications-panel) lands,
the override gains Filament's second condition — an inline notification never gets
it, so opening a list of stored notifications does not replay every past error
assertively.

### The default sanitizer is stricter than the one Filament tunes

`str($title)->sanitizeHtml()` **is** `symfony/html-sanitizer`: Filament's macro
resolves Symfony's `HtmlSanitizerInterface`, so the engine on both sides is the
same one, reached here through the twig-bridge
[`sanitize_html`](../../../packages/notifications/templates/partials/Notification/index.html.twig)
filter (declared `is_safe: ['html']`, auto-wired by FrameworkBundle + TwigBundle
the moment the component is installed — zero consumer config). What differs is the
**configuration**. Sylament renders through Symfony's `default` sanitizer, which
allows safe elements only.
[Filament tunes theirs further](../../../vendor/filament/support/src/SupportServiceProvider.php):
`class` and `style` on every element, six **named** data attributes and no
wildcard (`data-color`, `data-cols`, `data-col-span`, `data-from-breakpoint`,
`data-id`, `data-type`), `width`/`height` on `img` only, relative links and media,
and `withMaxInputLength(500000)` — the settings a rich-text editor's output needs.

So markup that survives in Filament (a styled `<span class="…">` in a body) is
stripped of its attributes here. A consumer who wants Filament's latitude
reconfigures `framework.html_sanitizer`; a sylament-tuned **named** sanitizer is
the evolution path if the toolkit ever ships rich content of its own. Nothing
about this is a divergence in the port — the same library, a different default
config — but it is the one thing a reader would get wrong from the template alone.

### Toast actions cannot open modals at Filament parity

When the [actions row](#actions-on-a-notification-wait-on-actiontoarray) lands, its
actions will dispatch events and open URLs, and they will **not** mount modals —
and that is parity, not a shortfall.
[`Action::toArray()`](../../../vendor/filament/actions/src/Action.php) serializes
no modal configuration and no closures, and Filament's `Notifications` Livewire
component implements no actions-host contract, so a Filament toast action cannot
mount a modal either. Worth stating because the obvious reading of "actions on a
notification" is that a toast can ask for confirmation, and neither codebase can.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### `make()` mints the id itself, because the resolver takes no constructor arguments

Filament seals `Notification::__construct(string $id)` and fills it through
`app(static::class, ['id' => $id ?? Str::orderedUuid()])`. Sylament's `make()`
resolves the instance first — through
[`Component::newInstance()`](../support/component.md#newinstance-routes-every-make-through-a-locator)
and its class-keyed service locator, which cannot pass constructor arguments — then
calls the existing `id()` setter with `$id ?? Uuid::v7()->toRfc4122()`
(`symfony/uid` translating `Str::orderedUuid()`, same time-ordered v7 shape). The
constructor is gone entirely, so a user subclass can declare constructor
dependencies of its own.

This is the global
[`make()` arguments move from constructors to setters](../renames.md#make-arguments-move-from-constructors-to-setters)
rule applied here, and it is invisible at the call site: `Notification::make()` and
`Notification::make('my-id')` both behave exactly as they do in Filament.

### `fromArray()` drops a value it cannot type, where Filament's typed setters throw

Every key comes back through a type check: a non-string `title`, `body` or
`status` becomes `null`, a `duration` that is neither `int` nor `string` falls back
to the default, and a `color`/`iconColor` array is accepted only when **every**
shade in it is a string — one non-string entry drops the whole map.

Filament's `fromArray()` assigns each key straight into the setter, and those
setters are typed unions, not `mixed` —
[`title(string | Closure | null)`](../../../vendor/filament/notifications/src/Concerns/HasTitle.php),
[`body(...)`](../../../vendor/filament/notifications/src/Concerns/HasBody.php),
[`status(...)`](../../../vendor/filament/notifications/src/Concerns/HasStatus.php),
[`duration(int | string | Closure | null)`](../../../vendor/filament/notifications/src/Concerns/HasDuration.php).
So a junk value out of the session is an uncaught `TypeError` there, and the port
is the **tolerant** arm rather than the strict one: the notification still renders,
minus the field that arrived malformed.

Tolerant is the deliberate reading here, and it does not soften the house
fail-fast rule — that rule is about **our own callers'** mistakes, and this is a
deserializer reading input it does not control. The values cross a session
boundary, so they are attacker-adjacent by the time `fromArray()` sees them, and
the reader is a *delivery* path: one malformed entry throwing would take down the
page that was trying to show the user a message, and take the other notifications
in the bag with it. A field that does not type is a data fact, not a bug, so
it is dropped and the rest of the notification is delivered. Both arms are pinned
by the junk-type and array-shade cases in
[`NotificationTest`](../../../packages/notifications/tests/Unit/NotificationTest.php).

### A `ScalableIcon` crosses the session unflattened and resolves at render size

Filament's `toArray()` resolves a scalable icon at serialization time — `$icon
instanceof ScalableIcon` takes `getIconForSize(IconSize::Large)`, a hardcoded
size chosen before anyone knows how the toast will render it — and only a plain
`BackedEnum` falls through to `->value`. The port inverts the order of the same
two arms: a `BackedEnum` that implements
[`ScalableIcon`](../../../packages/support/src/Contracts/ScalableIcon.php) is
**kept as the enum** (the session PHP-serializes enums; `fromArray()` admits a
`BackedEnum` back through `icon()`), and only a plain enum flattens to its value.
Resolution then happens where every other icon resolves — in
[`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php)'s
`ScalableIcon` → `BackedEnum` cascade, at the size the template actually passes —
so a scalable icon is size-correct on the delivery path where Filament's is
pinned to `Large` regardless of what renders it. The round trip is pinned by the
`ScalableFixtureIcon` case in
[`NotificationTest`](../../../packages/notifications/tests/Unit/NotificationTest.php).

### `send()` always writes the flash bag, and the live arm decides late

Filament's `send()` pushes onto the Laravel session under
`filament.notifications`; a Livewire `on('dehydrate')` hook then decides, per
request, whether to hand the pending notifications to the live stack. Sylament's
`send()` does the same one thing — push `toArray()` onto the flash bag under
[`sylament.notifications`](../../../packages/notifications/src/Delivery/SessionNotifications.php)
— with no branching at send time either. What changes is everything downstream of
it, and that is recorded where the machinery lives: the
[`PreRenderEvent` subscriber](notifications-bundle.md#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook)
that replaces the dehydrate hook, and the
[browser event carrying rendered HTML](notifications-bundle.md#there-is-no-claimed-notifications-key-because-the-toast-travels-in-the-same-response)
that replaces Livewire's re-request.

Two consequences belong to `send()` itself. It needs the container-built instance —
`RequestStack` reaches the object through a `#[Required]` setter, so a
`new Notification()` throws a `LogicException` naming the fix rather than silently
dropping the notification (pinned by
[`SendTest`](../../../packages/notifications/tests/Integration/SendTest.php)). And
an out-of-request `send()` (console command, worker) fails fast on the missing
session: a toast is a browser artifact, and the delivery arms that work without a
browser are the database and broadcast ones, both deferred above.

### The status fallbacks for icon and icon colour live in the template

Filament overrides two support concerns on the notification: `Concerns\HasIcon`
aliases the base getter and falls back by status to
`FilamentIcon::resolve(NotificationsIconAlias::NOTIFICATION_*) ?? Heroicon::Outlined*`,
and `Concerns\HasIconColor` falls back to the status string. Sylament composes
support's
[`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) and
[`HasIconColor`](../../../packages/support/src/Components/Concerns/HasIconColor.php)
unchanged and resolves both fallbacks in
[the toast template](../../../packages/notifications/templates/partials/Notification/index.html.twig)
instead — a `statusIcons` map keyed by status, the matching
[`NotificationsIconAlias`](notifications-icon-alias.md) constant passed as the
alias argument (only when the user set no icon of their own, so an explicit icon
is never re-aliased), and `component.getIconColor ?? status` for the colour.

The shape is the one tables already ships: its
[table partial](../../../packages/tables/templates/partials/Table/index.html.twig)
writes the empty-state icon as `:icon="emptyStateIcon ?? 'heroicons:x-mark'"` with
`:alias="emptyStateIcon ? null : constant('…::EMPTY_STATE')"`, which is this
template's expression with one status instead of four. A fallback chain the
template can compute is template data, and putting it in PHP would mean a getter
whose value depends on a second getter — exactly what
[`getExtraTemplateData()` is told to keep out](../../../CLAUDE.md). The rendered
result is identical and the setters are untouched — `icon()` and `iconColor()` take
the same arguments as Filament's.

The **getters** are where it shows, and it is a real API difference rather than an
invisible one. Filament's `Notification::getIcon()` / `getIconColor()` return the
status fallback (`Heroicon::OutlinedCheckCircle`, `'success'`); these return only
what the user set, so a status-only notification answers `null` from both — and
`toArray()` therefore carries `null` in the `icon` and `iconColor` keys where
Filament's carries the resolved icon and the status string. Nothing here reads
either: the template resolves the chain itself, and `fromArray()` re-derives it the
same way on the far side of the session. Reversal condition: a **PHP** consumer
that needs the resolved icon — the database panel is the plausible one — moves the
chain onto the class, where the template then reads it as one getter.

### A closure duration evaluating to `null` throws, where Filament falls back to persistent

Filament's `getDuration()` ends `?? 'persistent'`. That fallback is unreachable for
a literal — the property is typed `int|string|Closure` and `duration(null)` already
stores `'persistent'` — so it fires exactly once: when a **closure** duration
evaluates to `null`. Sylament's getter returns `$this->evaluate($this->duration)`
against an `int|string` return type, so that same case is a `TypeError` at the
boundary rather than a silent persistent toast, pinned by
[`HasDurationTest`](../../../packages/notifications/tests/Unit/Concerns/HasDurationTest.php).
`duration(null)` still means persistent, because the **setter** coerces it, exactly
as Filament's does.

Fail-fast over a silent fallback, per the house rule: a closure that returns `null`
by accident produces a toast that never leaves the screen, and the type error names
the callback that did it.

### The toast trades Alpine's transition classes for CSS and a Stimulus controller

Filament animates a toast with four `x-transition:*` class hooks driven by Alpine,
and re-flows the stack with a 60-line `Livewire.interceptMessage` FLIP: measure
each toast's `top` before the morph, measure again after, and play the difference
back through the Web Animations API — with a `prefers-reduced-motion` early return,
because `element.animate()` is not covered by a CSS reduced-motion reset.

Sylament's toast enters with `@starting-style` (which covers a page-load render and
an appended one identically, the same mechanism the modals epic used) and leaves by
animating its own height to zero before removing itself, so siblings reflow through
the same CSS transition; `prefers-reduced-motion` is one media query. The
[controller](../../../packages/notifications/assets/src/controllers/notification_controller.ts)
adds a `transitionend` listener plus a 400 ms `setTimeout` fallback so a toast is
removed even when the transition never fires (a hidden tab).

The FLIP is not reproducible here and does not need to be: it exists because
Filament's stack is re-rendered by the server, so a removed toast's siblings jump
between morphs. Here removal is client-owned — the element takes itself out of the
DOM and nothing re-renders behind it — which is the same property that makes the
[zombie-toast class](notifications.md#a-toast-closes-client-side-and-is-never-re-rendered)
impossible.

### The Laravel array and embedded-view contracts have no counterpart

Filament's class declares `implements Arrayable, HasEmbeddedView`. Neither is
ported. `Illuminate\Contracts\Support\Arrayable` is a Laravel marker for
`toArray()`, which PHP needs no interface to call and Symfony ships no equivalent
of; `HasEmbeddedView` marks a component that renders through `toEmbeddedHtml()`
instead of a view file, and Sylament's rendering seam is
`ViewComponent::render(Environment $twig)` against a Twig template — the same
Blade → Twig translation recorded in
[renames.md](../renames.md#idiom). `toArray()` itself ships unchanged, and it is
what crosses the session boundary either way.
