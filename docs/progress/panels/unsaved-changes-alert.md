---
description: The browser's own "leave site?" prompt, raised when a page is navigated away from with edits it has not saved or a modal action still mounted. (draft)
ports:
  - vendor/filament/filament/src/Pages/Concerns/HasUnsavedDataChangesAlert.php
  - vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php
  - vendor/filament/filament/resources/js/unsaved-changes-alert.js
owns:
  - packages/panels/assets/src/controllers/unsaved_changes_controller.ts
  - packages/panels/assets/src/utils/
  - packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php
  - packages/panels/src/Panel/Concerns/HasUnsavedChangesAlerts.php
---
# The unsaved-changes alert (panels)

The browser's own "leave site?" prompt, raised when a page is navigated away from with edits it has not saved or a modal action still mounted.
Three pieces: [`Pages\Concerns\HasUnsavedDataChangesAlert`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) (the `savedDataHash` marker, `rememberData()` and the per-page switch), the panel knob [`Panel::unsavedChangesAlerts()`](../../../packages/panels/src/Panel/Concerns/HasUnsavedChangesAlerts.php), and a fourth `@sylament/panels` Stimulus controller, [`sylament--panels--unsaved-changes`](../../../packages/panels/assets/src/controllers/unsaved_changes_controller.ts), bound to a sentinel `<div>` the page partials emit inside the live root.

It ports [`Pages/Concerns/HasUnsavedDataChangesAlert.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasUnsavedDataChangesAlert.php), [`Panel/Concerns/HasUnsavedChangesAlerts.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php) and the first and third functions of [`resources/js/unsaved-changes-alert.js`](../../../vendor/filament/filament/resources/js/unsaved-changes-alert.js).
The PHP surface is Filament's name for name; the JavaScript is not, and the reason is the whole of this file: the hash Filament compares in the browser **cannot be recomputed there** under Sylament's binding defaults, so the port compares three client-side signals instead and treats the hash as a marker.

## Surface

Every point of the alert.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$savedDataHash`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) | `#[Locked] public string $savedDataHash` | done | — | [diverges](#the-prop-is-a-nullable-liveprop-where-filaments-is-an-uninitialised-locked-string) |
| [`rememberData()`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) | the same | done | — | [semantics](#the-alert-is-armed-by-explicit-rememberdata-calls-because-ux-has-no-mounttrait-hook) |
| [`hasUnsavedDataChangesAlert()`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) on the trait, and the `false` default on [`Pages\Page`](../../../packages/panels/src/Pages/Page.php) | the trait method alone | done | — | [semantics](#a-page-overrides-the-method-never-the-property) |
| [`Panel::unsavedChangesAlerts()`](../../../packages/panels/src/Panel/Concerns/HasUnsavedChangesAlerts.php) + `hasUnsavedChangesAlerts()` | the same pair | done | — | — |
| The sentinel `<div class="sy-pa-unsaved-changes">` on both page partials | the two `@script` blocks in `page/index.blade.php` | done | — | [semantics](#the-sentinel-is-an-inner-element-because-a-second-data-controller-would-take-the-live-binding-down) |
| [`unsaved_changes_controller.ts`](../../../packages/panels/assets/src/controllers/unsaved_changes_controller.ts) — the three OR-ed signals | `setUpUnsavedDataChangesAlert` + `setUpUnsavedActionChangesAlert` | done | — | [semantics](#the-guard-is-three-or-ed-client-signals-not-one-hash-comparison) · [diverges](#no-hash-is-ever-computed-in-the-browser-so-the-prop-is-a-marker) |
| The disarm on a redirect | `$wire.__instance.effects.redirect` | done | — | [semantics](#the-disarm-is-a-location-header-on-the-render-response) |
| [`savedHashValueChanged()`](../../../packages/panels/assets/src/controllers/unsaved_changes_controller.ts) — the re-arm | no counterpart — Filament re-reads `$wire.savedDataHash` on every check | done | — | [semantics](#savedhash-is-a-re-arm-trigger-not-the-comparand) |
| [`utils/live_host.ts`](../../../packages/panels/assets/src/utils/live_host.ts) — the third copy | no counterpart | done | — | [Assets](assets.md) |
| The `unsaved-changes` block in [`package.json`](../../../packages/panels/assets/package.json), `fetch: eager` | no counterpart — Filament ships one asset bundle | done | — | [diverges](#an-app-that-omits-the-controller-block-loses-the-guard-silently) |
| `mountHasUnsavedDataChangesAlert()` | the trait's Livewire mount hook | skipped | — | [renames.md](../renames.md#renames) · [semantics](#the-alert-is-armed-by-explicit-rememberdata-calls-because-ux-has-no-mounttrait-hook) |
| `js-md5` and the client-side hash | `window.jsMd5(...)` | skipped | — | [diverges](#no-hash-is-ever-computed-in-the-browser-so-the-prop-is-a-marker) |
| `setUpSpaModeUnsavedDataChangesAlert` — the SPA arm | the same | skipped | — | [diverges](#filaments-spa-arm-has-no-counterpart) |
| The bfcache re-baseline | Filament re-reads `$wire.data` on every check, so a restore compares fresh | done | — | [diverges](#a-bfcache-restore-keeps-the-guard-armed-rather-than-re-baselining) |
| A confirm dialog with configurable body text | the `body` argument of the SPA arm | skipped | — | [diverges](#filaments-spa-arm-has-no-counterpart) |
| _rest not audited yet_ | — | — | — | — |

Covered by [`Unit/Pages/Concerns/HasUnsavedDataChangesAlertTest`](../../../packages/panels/tests/Unit/Pages/Concerns/HasUnsavedDataChangesAlertTest.php) (the null marker while the alert is off, the hash taken when it is on, a different hash after a change, the page override beating the panel, and the panel knob arming a page that names nothing), [`Unit/Panel/Concerns/HasUnsavedChangesAlertsTest`](../../../packages/panels/tests/Unit/Panel/Concerns/HasUnsavedChangesAlertsTest.php) (off by default; the bare, conditional and closure arms), [`Integration/Twig/PageRenderTest`](../../../packages/panels/tests/Integration/Twig/PageRenderTest.php) (the rendered sentinel's three values, the actions-only arm on a page with no `data`, the absence of a sentinel on a panel that guards nothing, the negative that the live root's `data-controller` is exactly `live`, and the hash changing across a `save()`), [`Integration/Controllers/PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php) (the same inner-element pin on the simple page), [`Integration/Resources/Pages/EditRecordTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordTest.php) (a composing page still marking nothing while its panel's knob is off) and [`Integration/SylamentPanelsBundleTest`](../../../packages/panels/tests/Integration/SylamentPanelsBundleTest.php) (both built files resolving under `@sylament/panels`).
The `beforeunload` prompt itself is browser behaviour and is verified in a browser pass, not by a test.

## Semantics

### A page overrides the method, never the property

`Pages\Page` declares `hasUnsavedDataChangesAlert(): bool { return false; }`, so every page partial can ask any page.
The trait overrides it with `$this->hasUnsavedDataChangesAlert ?? $this->getPanel()->hasUnsavedChangesAlerts()`, and a trait method outranks an inherited one, so composing the trait is what opts a page into the panel knob.

The `?bool $hasUnsavedDataChangesAlert` property is for **subclasses of a page that already composes the trait** — Filament's own shape, where `CreateRecord` composes and a user's page sets the property.
A class that composes the trait *and* redeclares the property with a different default is a PHP fatal at class load ("definition differs and is considered incompatible"), which the test kernel's recursive fixture load turns into a red suite rather than a readable error.
So a composing page that wants the alert unconditionally overrides the **method**, as [`GuardedSettingsPage`](../../../packages/panels/tests/Fixtures/Pages/GuardedSettingsPage.php) does with a docblock saying why.

### The alert is armed by explicit `rememberData()` calls, because UX has no `mount<Trait>()` hook

Livewire calls `mountHasUnsavedDataChangesAlert()` automatically for any trait whose name matches, and Filament's fills the hash at mount.
UX LiveComponent has no such convention, so the four call sites are written out: `CreateRecord::mount()` and `EditRecord::mount()` call `rememberData()` after `fillForm()`, and `create()` / `save()` call it after the write and before the notification — Filament's own four positions.

`rememberData()` is a no-op unless the alert is on, so a page composing the trait under a panel that guards nothing marks nothing and its `savedDataHash` stays `null`.
The hash itself is Filament's expression verbatim: `md5(str_replace('\\', '', (string) json_encode($this->data, JSON_UNESCAPED_UNICODE)))`.
`$this->data` is the composing page's own writable `LiveProp`; the trait is composed only by pages that declare one.

### The sentinel is an inner element, because a second `data-controller` would take the live binding down

Both page partials emit `<div class="sy-pa-unsaved-changes" {{ stimulus_controller('sylament--panels--unsaved-changes', {…}) }}></div>` as a child of the page root, rendered when `this.hasUnsavedDataChangesAlert or this.getPanel.hasUnsavedChangesAlerts` — so the actions-only arm still arms on a page that hosts no `data` at all.
It is never put on the live root, for the reason [`GlobalSearch` already records](global-search.md#the-stimulus-controller-rides-the-inner-element-not-the-live-root): `attrs()` resolves a repeated attribute later-source-wins, so a second `data-controller` there replaces `live` and the whole component stops working with no error.
`PageRenderTest` pins the sentinel's three values **and** the negative — the root's `data-controller` is exactly `live`.

The three values are the controller's whole input: `savedHash` (the marker, or `''`), `dataModel` (`'data'` when the page arms the data half, `''` otherwise) and `guardActions` (the panel knob).

### The guard is three OR-ed client signals, not one hash comparison

`#isDirty()` answers true when any of these holds:

1. **A typed-but-unsent edit.**
   `component.valueStore.getDirtyProps()` carries a key equal to the data model or prefixed by it (`data.title`).
   This is the signal Filament does not need and the port cannot do without: under Sylament's default `norender|` binding a keystroke never reaches the server, so `getData('data')` still holds the last synced value while the input on screen does not.
2. **A synced-but-unsaved edit.**
   `JSON.stringify(component.getData(dataModel))` differs from the baseline the controller took.
   This catches a field bound `live()`, whose value *has* reached the store.
3. **A mounted action.**
   `component.getData('mountedActions')` is non-empty and `guardActions` is on — Filament's `setUpUnsavedActionChangesAlert` over `HasActions`' existing prop.
   The prop it reads is the page host's own: a nested relation manager keeps its own `mountedActions`, so a modal opened from a manager does not arm the page guard — Filament's page-level alert has the same scope (observed in a browser pass, recorded rather than fixed).

The controller only ever **reads**: `getData()`, `valueStore.getDirtyProps()` and the `mountedActions` prop, never `set()`.
`savedDataHash` is not writable, so a write would surface as a `HydrationException` on the next request rather than as anything visible in the browser.

The host is resolved once in `connect()` through `resolveLiveHost()` under a `#generation` guard, with the same silent `catch { return; }` the tables controller uses: with no live host there is no state to guard, and staying inert is better than prompting on every navigation against an empty baseline.

### `savedHash` is a re-arm trigger, not the comparand

The controller never compares `savedHashValue` to anything.
What it does is re-take its own baseline when the value **changes**: a live morph re-stamps the sentinel's attribute in place without reconnecting the controller, and Stimulus fires `savedHashValueChanged()` for exactly that.
So a save that returns no redirect — the ordinary `EditRecord::save()` — re-arms the guard against the state it just wrote, and navigating away afterwards prompts nothing.

That is why the marker's *value* never has to be reproducible in the browser, and why the prop can be an opaque md5 that no JavaScript hashes.
It also means the one thing the server must do is change the hash whenever it saves, which is what `rememberData()`'s position after the write guarantees and what `PageRenderTest`'s *"re-marks the saved data across a save"* case pins.

### The disarm is a `Location` header on the render response

`#navigating` is set from the `render:started` hook when the backend response carries a `Location` header, and cleared on `render:finished`.
That is the only hook UX fires on a redirect — a redirect response never reaches `render:finished` — so it is the port's equivalent of Filament's `$wire.__instance.effects.redirect` probe, and it is a *response* fact rather than a submit flag: any live action that redirects disarms the guard, and one that does not leaves it armed.

## Divergences

### No hash is ever computed in the browser, so the prop is a marker

Filament's `beforeunload` handler recomputes `jsMd5(JSON.stringify($wire.data).replace(/\\/g, ''))` on the client and compares it with `$wire.savedDataHash`.
That comparison is unavailable here: `$wire.data` in Livewire tracks every keystroke, while under Sylament's `norender|` binding the equivalent store value lags the input, so a recomputed hash would match the saved one on a page full of unsent edits and the prompt would never fire.

So the port keeps the PHP surface — a server-computed md5 in a `LiveProp` — and gives it a different job: it is a **marker that the saved state changed**, read only through [the re-arm callback](#savedhash-is-a-re-arm-trigger-not-the-comparand).
`js-md5` is not a dependency and no hashing ships in the controller.

The trade is honest in both directions.
The port catches a case Filament misses — a typed-but-unsent edit — through the dirty-props scan.
And it is coarser in one: an edit typed and then typed back to its original value leaves the prop dirty, so the port prompts where Filament's hash comparison would not.
Prompting on a no-op edit is the safe side of that error.

### The prop is a nullable `LiveProp` where Filament's is an uninitialised `#[Locked]` string

Filament declares `#[Locked] public string $savedDataHash` with no initialiser and fills it at mount.
Read before `rememberData()` ran, that is a typed-property access error in PHP — harmless there because the mount hook always runs first.
Here `mount()` is the page's own and the trait cannot guarantee it, and the partial reads the prop on every render, so it is `#[LiveProp] public ?string $savedDataHash = null`: a page under a panel that guards nothing renders `null` and the template falls back to `''`.

There is no `#[Locked]` equivalent to port — UX props are non-writable unless declared `writable: true`, which this one is not, so the client cannot set it in the first place.

### Filament's SPA arm has no counterpart

`setUpSpaModeUnsavedDataChangesAlert` hooks `livewire:navigate` and raises a `confirm()` dialog with configurable body text before an SPA navigation.
There is no `Livewire.navigate` here — the shell is [deliberately Turbo-indifferent](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) and every panel navigation is a real page load — so the only hook is `beforeunload`, whose prompt text the browser owns.
The configurable body string goes with it: nothing can display it.

### A bfcache restore keeps the guard armed rather than re-baselining

Filament re-reads `$wire.data` on each `beforeunload`, so a page restored from the back/forward cache compares whatever the restored `$wire` holds.
Ours takes its baseline in `connect()`, and a bfcache restore does **not** reconnect the controller: the baseline taken before the page was frozen survives, the dirty state survives with it, and the guard stays armed.
So navigating away from a restored page with unsaved edits still prompts, which is the behaviour the guard exists for.

Recorded because the opposite is the natural prediction (a restore re-baselining and therefore *not* prompting); the shipped controller does not re-baseline, and the browser pass is what confirms which of the two the user sees.

### An app that omits the controller block loses the guard silently

The `unsaved-changes` block is declared `fetch: eager`, and for a sharper reason than the other three panels controllers: a guard that arms late is absent exactly when it matters.
It shares their failure mode, though — a consuming application that leaves the block out of `assets/controllers.json` navigates away from unsaved edits with no prompt and no error anywhere.
The page still renders and still saves, so the omission is degraded rather than broken, which is why it is recorded here and in [the asset notes](../../internals/assets.md#how-does-a-controller-reach-a-consuming-application) rather than guarded by the toolkit.
