---
description: The notifications bundle class, the session and live delivery pair it wires, its asset registration and the package manifest.
ports:
  - vendor/filament/notifications/src/NotificationsServiceProvider.php
  - vendor/filament/notifications/composer.json
  - vendor/filament/notifications/resources/css/index.css
  - vendor/filament/notifications/resources/js/index.js
owns:
  - packages/notifications/assets/src/styles/index.css
  - packages/notifications/src/Delivery/
  - packages/notifications/src/SylamentNotificationsBundle.php
---
# SylamentNotificationsBundle (notifications)

[`Sylament\Notifications\SylamentNotificationsBundle`](../../../packages/notifications/src/SylamentNotificationsBundle.php) is the notifications bundle class, documented here with the delivery pair it wires, [`SessionNotifications`](../../../packages/notifications/src/Delivery/SessionNotifications.php) (the flash-bag key, with `push()` / `pull()`) and [`LiveDeliverySubscriber`](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php) (the live arm), and with the package manifest it ships behind.
It ports Filament's [`NotificationsServiceProvider`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php), Spatie's `PackageServiceProvider` lifecycle becoming a Symfony `AbstractBundle` via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php).
What the port adds on top of the provider's responsibilities is container wiring, which Filament needs none of.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Notification`, the delivery pair, the colour policies and the Twig component as services](../../../packages/notifications/src/SylamentNotificationsBundle.php) | — (Sylament-only; Filament's provider binds no services) | done | — | [diverges](#the-bundle-registers-container-services-which-filaments-provider-has-no-counterpart-for) |
| [`SessionNotifications` — the `sylament.notifications` flash key](../../../packages/notifications/src/Delivery/SessionNotifications.php) | [`session()->push()`](../../../vendor/filament/notifications/src/Notification.php), [`session()->pull()`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | done | — | [diverges](#the-flash-bag-replaces-laravels-session-push-and-owns-the-key) |
| [`LiveDeliverySubscriber` — the live arm](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php) | [`on('dehydrate', …)`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | done | — | [diverges](#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) · [diverges](#there-is-no-claimed-notifications-key-because-the-toast-travels-in-the-same-response) · [diverges](#a-send-during-the-acting-components-own-render-waits-for-the-next-request) · [semantics](#a-redirecting-live-action-keeps-its-toast-for-the-destination-page-for-free) |
| [AssetMapper prepend — `@sylament/notifications`](../../../packages/notifications/src/SylamentNotificationsBundle.php) | [`FilamentAsset::register([Js::make('notifications', …)])`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php), [`resources/js/index.js`](../../../vendor/filament/notifications/resources/js/index.js) | in progress | — | [detail](#nothing-asserts-the-assetmapper-prepend) · [diverges](#asset-registration-is-an-assetmapper-prepend-and-the-css-rides-supports-build) |
| [Stylesheet entry point](../../../packages/notifications/assets/src/styles/index.css) | [`resources/css/index.css`](../../../vendor/filament/notifications/resources/css/index.css) | done | — | [diverges](#asset-registration-is-an-assetmapper-prepend-and-the-css-rides-supports-build) |
| [`@SylamentNotifications` template namespace](../../../packages/notifications/templates) | [`->hasViews()`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | done | — | [diverges](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [The package manifest's `symfony/*` requires](../../../packages/notifications/composer.json) | [`notifications/composer.json`](../../../vendor/filament/notifications/composer.json) | done | — | [diverges](#the-manifest-declares-every-symfony-package-src-imports) |
| `Livewire::component('notifications', …)` | [`packageBooted()`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | done | — | [diverges](#the-stack-is-registered-as-a-twig-component-not-a-live-one) |
| `Livewire::component('database-notifications', …)` | [`packageBooted()`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | blocked | the database-notifications panel | [detail](#the-database-notifications-registration-waits-on-its-panel) |
| Translations (`->hasTranslations()`) | [`configurePackage()`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | todo | — | [detail](#the-translation-catalogue-is-unported-so-the-close-label-is-an-english-literal) |
| `Testable::mixin(new TestsNotifications)` | [`packageBooted()`](../../../vendor/filament/notifications/src/NotificationsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### Nothing asserts the AssetMapper prepend

`prependExtension()` registers `packages/notifications/assets/dist` into `framework.asset_mapper.paths` under the alias `@sylament/notifications`, but only when [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) confirms AssetMapper is installed, the guard that keeps `symfony/asset-mapper` optional for production consumers.
The shape and its reasoning are the [divergence below](#asset-registration-is-an-assetmapper-prepend-and-the-css-rides-supports-build).

What is missing is a test.
Nothing asserts the path and alias are prepended when AssetMapper is present, and nothing exercises the negative branch of `isAssetMapperAvailable()` (the short-circuit that returns without prepending).
The prepend, unlike the manifest, is assertable against the compiled container, which is why its row is **in progress** rather than done, on the same terms support's [identical row](../support/support-bundle.md#asset-registration) holds.

**Next step:** an Integration test asserting `@sylament/notifications` maps the built directory, plus a Unit test for the guard's false branch, and then this row flips to **done**.
The positive half is written for actions: [`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) asks the real `AssetMapperInterface` for a built controller by source path and requires the logical path back, which is the same call StimulusBundle's controllers-map generator makes.
Copy it here; the guard's false branch is `support`'s to pin, since [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php) owns it for every bundle.

### The database-notifications registration waits on its panel

`Livewire::component('database-notifications', DatabaseNotifications::class)` registers the panel that lists a user's stored notifications, a separate Livewire component with its own actions host, its own pagination and its own polling interval.
Nothing of it is ported, so there is nothing to register.

**Next step:** build the database-notifications panel.
Unlike the toast stack, that component is free to be a UX **live** component: it holds stored server-side state by nature, which is what the toast stack deliberately [does not](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported).

### The translation catalogue is unported, so the close label is an English literal

`->hasTranslations()` registers [`resources/lang/`](../../../vendor/filament/notifications/resources/lang): the close-button label (`notification.php`) and the database-modal string set (`database.php`).
The visible consequence is in the toast template: the close button's `label` is the English literal `'Close notification'`, the way tables' `getEmptyStateHeading()` builds an English heading ([tables-bundle.md](../tables/tables-bundle.md#the-translations-namespace-is-not-registered)).

**Next step:** port with the toolkit-wide i18n pass, which is the same destination tables' translations row names.
Nothing about notifications makes its strings a special case, and doing them first would fix a catalogue shape the other packages then inherit.

## Semantics

### A redirecting live action keeps its toast for the destination page, for free

Filament's dehydrate hook guards on the redirect store (`store($component)->has('redirect')`) and skips delivery when the component is redirecting, so the notification stays in the session and renders on the page the user lands on.
The port needs no equivalent check: a redirecting live action returns a `Response`, so UX's `onKernelView()` never renders the component, no `PreRenderEvent` fires, the subscriber never runs, and the flash survives to the next request untouched.
Same semantics, arrived at by not having a code path rather than by adding a guard, which is why a reader looking for the redirect check finds nothing.
No test drives a redirecting live action; the third case of [`LiveDeliverySubscriberTest`](../../../packages/notifications/tests/Integration/Delivery/LiveDeliverySubscriberTest.php) pins the neighbouring guard, a live component rendered on a non-live request leaving the flash alone.

## Divergences

### The bundle registers container services, which Filament's provider has no counterpart for

`NotificationsServiceProvider` binds nothing into the Laravel container: a Filament `Notification` is built by `app(static::class, …)` and any dependency is filled reflectively without a registration.
Symfony's container cannot do that, so [`loadExtension()`](../../../packages/notifications/src/SylamentNotificationsBundle.php) registers four groups explicitly: `->set(Notification::class)` (at `src/` root, so it takes the same explicit `set()` actions' `Action` does rather than a `Components/` glob), the two `Delivery\` services, the two `Colors\Components\` globs, and the `Twig\Components\` glob for the stack.

Each one is load-bearing.
The `Notification` registration is what makes `make()` return a container-built, non-shared instance, which is in turn what lets the `#[Required]` `setSessionNotifications()` setter reach the real service; without it `send()` throws by design rather than writing nowhere.
The colour globs carry `->public()`, because `ColorManager` resolves a policy from the container **by class-string** and a private service is unreachable through `Container::get()`.
The `Delivery\` pair is `autoconfigure()`d, which is what tags `LiveDeliverySubscriber` as an event subscriber; the bundle nowhere mentions `kernel.event_subscriber`.

Permanent: the registration is the Symfony-idiom replacement for Laravel's reflective container, filed as a provider row because the provider is where the responsibility would sit if Laravel needed one, which is why the row's _Ports_ cell names no Filament source.

### The flash bag replaces Laravel's session push, and owns the key

Filament writes with `session()->push('filament.notifications', $data)` and reads with `session()->pull(...)`, in two different files.
Sylament routes both through one class, [`SessionNotifications`](../../../packages/notifications/src/Delivery/SessionNotifications.php), whose `FLASH_KEY` (`sylament.notifications`) is the only place the string appears and whose consumers are `send()`, the subscriber and the stack component.

The store is Symfony's **flash bag**, not a plain session array, and that is the substantive part: a flash entry is read-once by definition, so `pull()` is a `get()` and the "drain on read" behaviour both readers rely on is the bag's, not ours.
Two hardenings come with owning the accessor.
`pull()` filters the bag: an entry that is not an array, or is an array with a non-string key, is dropped rather than handed to `fromArray()`, because session contents are attacker-adjacent input by the time they are read.
And a session that is not `FlashBagAwareSessionInterface` raises a `LogicException` naming the type, while no session at all raises Symfony's own `SessionNotFoundException`, the fail-fast an out-of-request `send()` (console, worker) hits.

### The live-delivery decision point is a `PreRenderEvent` subscriber, not a dehydrate hook

Filament decides late, in a `Livewire::on('dehydrate')` hook that fires per component after render: is this a Livewire request, is it redirecting, is anything pending.
The port keeps "decide late" and moves the point earlier in the lifecycle, because the event it needs is ux-twig-component's [`PreRenderEvent`](../../../packages/notifications/src/Delivery/LiveDeliverySubscriber.php), subscribed at **priority 20**.

The priority is the whole reason the shape works, and it is not tunable: UX's own `AddLiveAttributesSubscriber` listens to the same event at priority 0 and drains `LiveResponder`'s queues into the component's root attributes (`LiveControllerAttributesCreator::attributesForRendering()` drains **and** resets).
A browser event queued after that point would be written into no response at all.
Two guards keep the subscriber to live components on live requests: the render metadata's `live` flag (so a plain Twig component rendered mid-request is ignored) and the `_live_component` request attribute on the **main** request (so full page loads no-op and the stack's own flash read stays the page-load path).
The second and third cases of [`LiveDeliverySubscriberTest`](../../../packages/notifications/tests/Integration/Delivery/LiveDeliverySubscriberTest.php) pin them in that order.

### A `send()` during the acting component's own render waits for the next request

The subscriber drains the flash at the start of a live component's render, on `PreRenderEvent`.
A `send()` that runs **after** that point, inside the acting component's own template rendering, finds the flash already read for this response, so the toast stays in the bag and appears on the next request that reads it (the next live round trip or page load) instead of the current one.
Every legitimate call site runs before render: live actions, `afterStateUpdated`, and the hooks around them.

Filament delivers that toast in the same response: Livewire renders the component and then triggers `dehydrate` ([`HandleComponents.php`](../../../vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php)), so a render-time `send()` is already in the session when the hook pulls it.
The port cannot follow, because the [priority-20 `PreRenderEvent`](#the-live-delivery-decision-point-is-a-prerenderevent-subscriber-not-a-dehydrate-hook) is the last point at which a queued browser event still reaches the response.

### There is no claimed-notifications key, because the toast travels in the same response

Filament's hook moves the pending notifications from `filament.notifications` into a second session key, `filament.claimed_notifications`, dispatches `notificationsSent`, and lets the stack's listener re-request and read the second key: a two-key dance protecting a race, since between the dispatch and the follow-up request another request could drain the first key.

The port has no race to protect against.
The subscriber renders each notification to HTML and queues it as a `sylament:notification` browser event on the response already in flight, so the content never waits in the session for a second request to come and fetch it.
One key, one response, and the "multiple sends in one request" case is simply multiple events.
This is [the same decision](notifications.md#live-delivery-appends-server-rendered-html-instead-of-re-requesting-the-stack) seen from the delivery side rather than the stack's.

### Asset registration is an AssetMapper prepend, and the CSS rides support's build

Filament registers one built bundle, `Js::make('notifications', __DIR__ . '/../dist/index.js')`, through `FilamentAsset`, and that bundle's `index.js` registers the `notificationComponent` Alpine plugin on `alpine:init`; its CSS reaches the page through the app's compiled theme.
Sylament's translation is a [`prependExtension()`](../../../packages/notifications/src/SylamentNotificationsBundle.php) mapping `packages/notifications/assets/dist` to `@sylament/notifications`, guarded by `isAssetMapperAvailable()` exactly as support's is, so a consumer without AssetMapper is not forced to install it.

The asset workspace behind it is **JS-only**: two Stimulus controllers declared in its `package.json` under `symfony.controllers`, so StimulusBundle picks them up with no consumer wiring.
Both import only `@hotwired/stimulus`, the one runtime and peer dependency the workspace declares and the one entry its `symfony.importmap` block names, because it is a remote entry no PHP package of ours installs.
The toast controller handles auto-dismiss, hover-pause and a [close-only click handler](notification.md#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload); a toast action's dispatch is delivered by actions' page-wide controller.

That puts a prerequisite on the consumer, and it is one a notifications reader will not otherwise go looking for: **a toast action's dispatch needs `@sylament/actions` enabled in the app's `assets/controllers.json`.**
Enabling `@sylament/notifications` alone buys auto-dismiss, hover-pause and close; the event itself is delivered by the other package's page-wide controller, which StimulusBundle never registers for an app that does not list it, with no error anywhere, since the close arm keeps working while the event goes nowhere.
What the entry looks like, how Flex writes it, and why the failure is silent: [actions-bundle.md](../actions/actions-bundle.md#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).

Its stylesheet is *not* built here: support's `assets/src/styles/index.css` imports this package's [`src/styles/index.css`](../../../packages/notifications/assets/src/styles/index.css), which imports `components/notification.css` into `layer(components)`, so the `sy-no-*` rules compile into support's single built stylesheet and reach the page through support's `core` controller.
One stylesheet for the toolkit, one build to keep byte-stable, and notifications ships no CSS dist of its own.

### The stack is registered as a Twig component, not a live one

`Livewire::component('notifications', Notifications::class)` names the stack for `@livewire('notifications')`.
The port's equivalent registration is the `Twig\Components\` glob, which autoconfigures the class as a `#[AsTwigComponent]`: a plain class-backed Twig component, invoked as `<twig:SylamentNotifications:Notifications />`.
The reasoning for **not** making it a live component is the stack's own [architecture note](notifications.md#the-stack-holds-no-server-side-collection-so-filaments-collection-is-not-ported); what belongs here is only that the bundle needs one glob rather than a named component registration, and that the same glob registers any later class in `Twig\Components\`, the database-notifications panel's live component included.

### The manifest declares every `symfony/*` package `src/` imports

Filament's notifications manifest requires `filament/actions`, `filament/support` and the Laravel/Livewire stack.
The port's [manifest](../../../packages/notifications/composer.json) requires `sylament/actions`, `sylament/support` and one Symfony package per thing `src/` or `templates/` cannot work without: `symfony/ux-twig-component` (the stack's `AsTwigComponent`, the subscriber's `PreRenderEvent`), `symfony/ux-live-component` (`LiveResponder`), `symfony/http-foundation` (`RequestStack`, the flash-bag interfaces), `symfony/event-dispatcher` (`EventSubscriberInterface`), `symfony/service-contracts` (the `#[Required]` attribute), `symfony/uid` (the v7 id) and `symfony/html-sanitizer` (the `sanitize_html` filter the toast template pipes its title and body through; a template that cannot resolve the filter does not render).
The [split-repo dependency rule](../../../CLAUDE.md) is why each is named rather than inherited through a sibling that already pulls it in.

`sylament/actions` is a `require` rather than a `suggest` because [`Concerns\HasActions`](../../../packages/notifications/src/Concerns/HasActions.php) and `Notification`'s two array methods import `Action` and `ActionGroup` unconditionally, for [toast actions](notification.md#toast-actions-are-re-viewed-as-small-links-and-the-actions-key-restores-them).
One absence is deliberate: `twig/twig` is not declared despite `Twig\Markup` and `Twig\Environment` appearing in `src/`, on the same grounds every other package leaves it out: `sylament/support` declares it and Twig cannot leave the graph.
The manifest is a declaration with no runtime to assert; what would catch a missing require is a consumer install and the CI split.

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` ends with `Testable::mixin(new TestsNotifications)`, which grafts `assertNotified()` / `assertNotNotified()` onto Livewire's fluent test object.
Sylament tests are Pest over `KernelTestCase`, with no `Testable` to mix into: the same **skipped** call the [tables](../tables/tables-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported) and [actions](../actions/actions-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported) bundles record.
What is skipped is the **mixin registration**, not the helpers: both `src/Testing/` files keep **todo** rows in the [package README](README.md#unported), and [what a Pest-shaped port would read instead](notification.md#the-assertion-helpers-read-a-collection-the-stack-does-not-hold) is written up with them.
