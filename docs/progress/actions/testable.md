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
| [`call()` / `set()`, fluent, and `component()` / `response()`](../../../packages/actions/src/Testing/Testable.php) | — (no Filament source) | done | — | [semantics](#the-kernel-catches-exceptions-and-the-tester-rethrows-the-original) |
| [`refresh()` / `emit()` / `submitForm()` / `actingAs()` / `setRouteLocale()` / `render()`](../../../packages/actions/src/Testing/Testable.php) | — (no Filament source) | in progress | — | [detail](#the-other-primitives-wait-for-a-test-that-needs-them) |
| `assertRedirect()` / `assertNoRedirect()` | — (Livewire's) | todo | — | — |
| `assertNotified()` / `assertNotNotified()` | [`TestsNotifications.php`](../../../vendor/filament/notifications/src/Testing/TestsNotifications.php) | todo | — | — |
| [`InteractsWithLiveComponents::createLiveComponent()`](../../../packages/actions/src/Testing/InteractsWithLiveComponents.php) | — (no Filament source) | done | — | [semantics](#createlivecomponent-builds-uxs-tester-through-uxs-own-method) |
| [`ExceptionRecorder`](../../../packages/actions/src/Testing/ExceptionRecorder.php), registered only in test mode | — (no Filament source) | done | — | [semantics](#the-recorder-exists-only-when-frameworktest-is-on) |

## Details

### The other primitives wait for a test that needs them

`refresh()`, `emit()`, `submitForm()`, `actingAs()`, `setRouteLocale()` and `render()` delegate to UX's tester as `call()` and `set()` do, and no test uses them yet; each is covered by the first test that does, since every ported helper is exercised by the suite.

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
