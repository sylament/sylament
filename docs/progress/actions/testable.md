---
description: The fluent tester a test drives a live component through, the test-case trait that creates it, and the test-mode recorder that hands it the exception a request threw.
ports: []
owns:
  - packages/actions/src/DependencyInjection/Compiler/ExceptionRecorderPass.php
  - packages/actions/src/Testing/ExceptionRecorder.php
  - packages/actions/src/Testing/InteractsWithLiveComponents.php
  - packages/actions/src/Testing/Testable.php
---
# Testable (actions)

[`Sylament\Actions\Testing\Testable`](../../../packages/actions/src/Testing/Testable.php) has **no Filament source**.
Filament attaches its testing helpers to Livewire's `Testable` as mixins reached through `__call()`, which hides them from PHPStan ([principles](../../internals/principles.md)); here they are real methods on a tester wrapping UX LiveComponent's `TestLiveComponent`, so a test chains them as Filament's do.
[`TestsActions`](tests-actions.md), [`TestsSchemas`](../schemas/tests-schemas.md) and [`TestsForms`](../forms/tests-forms.md) all compose onto this one class, because actions, schemas and forms require one another; tables' tester [extends it](../tables/testable.md) with tables' helpers.

A test case creates one through [`InteractsWithLiveComponents::createLiveComponent()`](../../../packages/actions/src/Testing/InteractsWithLiveComponents.php), which keeps UX's signature, `createLiveComponent(string $name, array $data = [], ?KernelBrowser $client = null)`, and like UX's requires a `KernelTestCase`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`call()` / `set()`, fluent, and `component()` / `response()` / `render()`](../../../packages/actions/src/Testing/Testable.php) | — (no Filament source) | done | — | [semantics](#the-kernel-catches-exceptions-and-the-tester-rethrows-the-original) · [semantics](#after-a-redirect-only-the-response-and-the-notifications-can-be-read) |
| [`refresh()` / `emit()` / `submitForm()` / `actingAs()` / `setRouteLocale()`](../../../packages/actions/src/Testing/Testable.php) | — (no Filament source) | in progress | — | [detail](#the-other-primitives-wait-for-a-test-that-needs-them) |
| [`assertRedirect()` / `assertNoRedirect()`](../../../packages/actions/src/Testing/Testable.php) | — (Livewire's `TestsRedirects`) | done | — | [semantics](#a-redirect-is-a-3xx-response-and-its-location-is-compared-as-a-full-url) · [diverges](#assertnoredirect-names-the-location-it-failed-on) |
| [`assertNotified()` / `assertNotNotified()`, a guarded optional over `sylament/notifications`](../../../packages/actions/src/Testing/Testable.php) | [`TestsNotifications.php`](../../../vendor/filament/notifications/src/Testing/TestsNotifications.php) | done | — | [semantics](#the-notification-assertions-read-what-the-testers-requests-sent) · [tests-notifications.md](../notifications/tests-notifications.md#a-title-matches-the-sent-title-and-a-notification-everything-but-the-id) |
| [`InteractsWithLiveComponents::createLiveComponent()`](../../../packages/actions/src/Testing/InteractsWithLiveComponents.php) | — (no Filament source) | done | — | [semantics](#createlivecomponent-builds-uxs-tester-through-uxs-own-method) |
| [`ExceptionRecorder`](../../../packages/actions/src/Testing/ExceptionRecorder.php), registered only in test mode | — (no Filament source) | done | — | [semantics](#the-recorder-exists-only-when-frameworktest-is-on) |

## Details

### The other primitives wait for a test that needs them

`refresh()`, `emit()`, `submitForm()`, `actingAs()` and `setRouteLocale()` delegate to UX's tester as `call()` and `set()` do, and no test uses them yet; each is covered by the first test that does, since every ported helper is exercised by the suite.

## Semantics

### The kernel catches exceptions, and the tester rethrows the original

UX's `TestLiveComponent` turns exception catching off on its client, so a failed validation surfaces as a thrown `ValidationException` and the 422 re-render a browser receives never happens.
The tester turns it back on, so a failed validation answers as the browser sees it: UX re-renders the component with status 422, the error is in the render, and the re-hydrated host still knows it through the sticky `validatedSchemas` prop.
Every other failure rethrows the exception the host threw, never an error page: after each request the tester takes what the [recorder](#the-recorder-exists-only-when-frameworktest-is-on) kept and throws it unless the response is a success, a redirect, or that 422.
The 422 counts only when it is UX's re-render (`application/vnd.live-component+html`), because an `UnprocessableEntityHttpException` thrown before UX mounted the component reaches the framework's error page with the same status.
A failing response with nothing recorded throws a `LogicException` naming the status.
UX sends the mount request on the tester's first use, so the tester makes it before anything reads a response or builds the next request from its props, and `setRouteLocale()` makes it again.

### `createLiveComponent()` builds UX's tester through UX's own method

The trait uses UX's `InteractsWithLiveComponents` with its `createLiveComponent()` aliased as `createTestLiveComponent()`, and defines its own `createLiveComponent()` returning the tester, so UX's construction is reused rather than copied.
It resolves `test.client` itself and hands the same client to UX and to the tester, because the service is not shared and the tester must turn catching back on for the client UX's tester sends through.

### The recorder exists only when `framework.test` is on

[`SylamentActionsBundle`](actions-bundle.md) registers `ExceptionRecorder` as a public `kernel.exception` listener at priority 128, ahead of UX's 422 re-render (20) and the framework's error page (-128), and [`ExceptionRecorderPass`](../../../packages/actions/src/DependencyInjection/Compiler/ExceptionRecorderPass.php) removes it unless `test.service_container` is defined.
FrameworkBundle defines that service only under `framework.test`, and an extension's `load()` runs on a container of its own that never sees another extension's services, so test mode is read in a compiler pass, which runs before the listeners are collected.
The recorder keeps the first exception since the tester last took one, because an exception thrown while the error page renders would otherwise replace the one the host threw.

### After a redirect, only the response and the notifications can be read

A redirect answers a raw 302 in test mode, with no component to re-hydrate, so after one `response()`, `assertRedirect()`, `assertNoRedirect()`, `assertNotified()` and `assertNotNotified()` keep working.
Anything else that reads or drives the component, `render()` and `component()` included, throws a `LogicException` naming the `Location`: `A live component action has redirected to [<location>] and you can no longer access the component.`, where UX's own message stops before the location.
`callAction()` reads the response, so a redirecting action ends the call without the exception.

### A redirect is a 3xx response, and its `Location` is compared as a full URL

`assertRedirect()` fails with Livewire's `Component did not perform a redirect.` unless the last response is a redirect, and with a URI it compares the `Location` through HttpFoundation's `ResponseHeaderLocationSame`, which resolves a relative URL against the request as Livewire's `url()` does, so `/products/1` and `http://localhost/products/1` match.
Livewire also takes a component class, resolved to its route; a live component here has no route of its own, so that arm is not ported.

### The notification assertions read what the tester's requests sent

After each of its requests the tester reads [`NotificationRecorder`](../notifications/tests-notifications.md#the-recorder-exists-only-when-frameworktest-is-on-and-answers-for-one-request) through the client's container and keeps what it found, and `assertNotified()` and `assertNotNotified()` hand that list to [`TestsNotifications`](../notifications/tests-notifications.md) and empty it.
Both make the mount request first, so a notification sent on mount counts.
`sylament/notifications` is a `suggest` of actions, so both are a guarded optional: without the package they throw a `LogicException` naming it, and with the package but no recorder in the kernel they throw one naming `SylamentNotificationsBundle` and `framework.test`, rather than pass on a list nothing recorded.
Each takes the list before it names `TestsNotifications`, because PHP resolves a static call's class before evaluating its arguments, so a guard inside the argument would surface as a class-not-found `Error` instead.
The suite's one kernel has both the package and its bundle, so neither refusal runs there; the guard's condition is pinned by use instead, since a renamed recorder or matcher dead-ends every `assertNotified()`, which every toast the suite asserts then fails.

## Divergences

### `assertNoRedirect()` names the location it failed on

Livewire's `assertNoRedirect()` asserts with no message, so it fails as `Failed asserting that false is true.`; here it fails as `Component performed a redirect to [<location>].`
