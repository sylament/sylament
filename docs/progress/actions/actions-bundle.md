---
description: The actions bundle class, its service, Twig and AssetMapper wiring, the page-wide dispatch controller, and what the package manifest declares.
ports:
  - vendor/filament/actions/src/ActionsServiceProvider.php
  - vendor/filament/actions/routes/web.php
  - vendor/filament/actions/database/migrations/
owns:
  - packages/actions/assets/src/controllers/
  - packages/actions/src/SylamentActionsBundle.php
---
# SylamentActionsBundle (actions)

[`Sylament\Actions\SylamentActionsBundle`](../../../packages/actions/src/SylamentActionsBundle.php) ports Filament's [`ActionsServiceProvider`](../../../vendor/filament/actions/src/ActionsServiceProvider.php): Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle` through [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php).
`loadExtension()` registers every action class, the host-side [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) component and the [`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php) as services, none of which Filament's provider binds, and `prependExtension()` maps `assets/dist` into AssetMapper as `@sylament/actions`, which is what makes the built dispatch controller reachable at all.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle registration and the `@SylamentActions` namespace](../../../packages/actions/src/SylamentActionsBundle.php) | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) (`hasViews()`) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [Action class services](../../../packages/actions/src/SylamentActionsBundle.php) (`configureComponents()`) | — | done | — | [semantics](#every-action-class-is-a-service-so-its-required-setters-run) |
| [Twig component service](../../../packages/actions/src/SylamentActionsBundle.php) (`configureTwigComponents()`) | — | done | — | [diverges](#the-bundle-registers-a-twig-component-service-which-filament-has-no-counterpart-for) |
| [Twig extension service](../../../packages/actions/src/SylamentActionsBundle.php) (`renderAction()`) | — | done | — | [diverges](#the-bundle-registers-a-renderaction-twig-extension-which-filament-has-no-counterpart-for) |
| [`@sylament/actions` asset workspace](../../../packages/actions/assets/package.json) | — | done | — | [diverges](#the-asset-workspace-is-js-only-and-its-importmap-block-names-only-stimulus) |
| [Page-wide dispatch controller](../../../packages/actions/assets/src/controllers/dispatch_controller.ts) | — | done | — | [diverges](#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it) |
| [The `@sylament/actions` AssetMapper prepend](../../../packages/actions/src/SylamentActionsBundle.php) | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) (`packageBooted()`) | done | — | [diverges](#asset-registration-is-an-assetmapper-prepend) |
| Exports/Imports provider wiring (`hasMigrations()`, `hasRoutes('web')`, the `filament.actions` middleware group, `hasCommands()`) | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) · [`routes/web.php`](../../../vendor/filament/actions/routes/web.php) | blocked | the Exports/Imports subsystem | [detail](#the-exportsimports-provider-wiring-waits-on-the-exportsimports-subsystem) |
| Translations (`hasTranslations()`) | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | todo | — | [detail](#translations-are-unported-independently-of-exportsimports) |
| `Testable::mixin(new TestsActions)` | [`ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) (`packageBooted()`) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### The Exports/Imports provider wiring waits on the Exports/Imports subsystem

Four provider calls exist solely to support Filament's background export and import feature, which has no Sylament port (the [package README](README.md) tracks `Exporter`, `Importer`, `ExportAction`, `ImportAction` and the `Exports/`, `Imports/` and `Commands/` subtrees):

- `->hasMigrations(['create_imports_table', 'create_exports_table', 'create_failed_import_rows_table'])` publishes Eloquent migrations for the [`Export`](../../../vendor/filament/actions/src/Exports/Models/Export.php) / [`Import`](../../../vendor/filament/actions/src/Imports/Models/Import.php) / `FailedImportRow` models.
  No Doctrine entity or migration equivalent exists.
- `->hasRoutes('web')` registers [`routes/web.php`](../../../vendor/filament/actions/routes/web.php): two `GET` routes (`DownloadExport`, `DownloadImportFailureCsv`) in a `filament.actions`-middleware, `filament`-prefixed group.
  No Symfony route equivalent exists.
- `packageRegistered()`'s `middlewareGroup('filament.actions', ['web'])` exists only to gate those two routes.
- `->hasCommands([MakeExporterCommand::class, MakeImporterCommand::class])` registers stub-publishing `make:` commands for user `Exporter`/`Importer` classes.
  No `Commands/` subtree exists in the port.

None of this can be ported before the subsystem itself: the migrations describe tables the port has no entities for, the routes serve controllers it does not have, and the commands generate classes it does not define.
All four are **blocked** on that subsystem rather than skipped, because background export and import is a wanted parity feature.
Filament runs those jobs on Laravel's queue; their Symfony counterpart is Messenger.

### Translations are unported, independently of Exports/Imports

`->hasTranslations()` registers Filament's `resources/lang` namespace: labels for the confirmation modal, notification titles, and generated import and export messages.
No translation catalogue exists in the port.
It does not wait on the Exports/Imports subsystem, because the confirmation-modal strings apply to the ported `Action` and [`CanRequireConfirmation`](can-open-modal.md).

## Semantics

### Every action class is a service, so its `#[Required]` setters run

`configureComponents()` registers `Action`, `ActionGroup` and every prebuilt action with `->set()`, autowired and autoconfigured.
A class the bundle does not name falls back to `new $class()` in [`ComponentResolver::instantiate()`](../../../packages/support/src/DependencyInjection/ComponentResolver.php), where every `#[Required]` setter on it (the manager registry a write body persists through, the event dispatcher) silently never runs and nothing errors.
[`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) pins the list for `AttachAction`, `BulkAction`, `BulkActionGroup`, `DetachAction`, `DetachBulkAction`, `ReplicateAction`, `ViewAction` and the associate family (`AssociateAction`, `DissociateAction`, `DissociateBulkAction`); `Action`, `ActionGroup`, `CreateAction`, `DeleteAction`, `DeleteBulkAction` and `EditAction` are named by no case, which is a gap in the test rather than a decision.
A new prebuilt action therefore lands with a line in `configureComponents()` and a name in one of those cases.
The same test boots the fixture Kernel's Doctrine rig ([`services_doctrine.php`](../../../packages/actions/tests/Fixtures/services_doctrine.php) and the single-manager [`FixtureManagerRegistry`](../../../packages/actions/tests/Fixtures/Doctrine/FixtureManagerRegistry.php)) beside `SylamentNotificationsBundle` and a mock-file session, because a record action's write body persists through the registry and sends a titled toast, and `Notification::send()` throws on an instance the container did not build.

## Divergences

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace.
The bundle registers no view namespace: `AbstractBundle::getPath()` returns `packages/actions`, and TwigBundle maps `packages/actions/templates` to `@SylamentActions` by convention.
That is why [`Action`](../../../packages/actions/src/Action.php) can default `$defaultTemplate` to `self::BUTTON_TEMPLATE` (`'@SylamentActions/partials/Action/Button.html.twig'`) with no explicit Twig path config.
Same divergence as [forms-bundle.md](../forms/forms-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) and [schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).
Every Integration test that renders an action through the fixture [`Kernel`](../../../packages/actions/tests/Fixtures/Kernel.php), [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php) among them, proves the bundle loads and its templates resolve.

### The bundle registers a Twig component service, which Filament has no counterpart for

Filament's Blade components need no service registration: Blade resolves `<x-filament-actions::modals />` from the view namespace.
Sylament's [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) is a class, so it has to be a container service tagged `twig.component`.
[`configureTwigComponents()`](../../../packages/actions/src/SylamentActionsBundle.php) loads the `Twig/Components/` directory with `->autowire()->autoconfigure()`, and autoconfiguration lets the `#[AsTwigComponent]` attribute supply the tag (ux-twig-component registers it for autoconfiguration), so the attribute is the single source of truth for the component's name and template.
`Modals` takes the component stack by **service id**, through `#[Autowire(service: 'ux.twig_component.component_stack')]` on its constructor, not by type: ux-twig-component registers no class alias for it, so autowiring by type would fail.
Registration is unconditional, unlike [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php)'s optional-dependency guard, because `symfony/ux-twig-component` is a hard `require` of this package.
[`ModalsRenderTest`](../../../packages/actions/tests/Integration/Components/ModalsRenderTest.php) covers it: `<twig:SylamentActions:Modals />` only resolves if the service was registered and tagged.

### The bundle registers a `renderAction()` Twig extension, which Filament has no counterpart for

Filament host templates render an action by echoing the object itself: `Action` extends a `ViewComponent` that implements `Htmlable` ([`ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)), so Blade renders `{{ $action }}` through `toHtml()` and the provider wires nothing.
Twig has no equivalent implicit-render path, so the bundle registers [`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php) (`sylament_actions.action_extension`, tagged `twig.extension`) supplying `renderAction(name)`, the actions twin of schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php), wired the same way: support's `ComponentExtension` arrives by autowiring through the class alias support registers for `sylament_support.component_extension`, and the function registers with `needs_environment` and `needs_context`.
The helper resolves the name through the **surrounding live component's** `getAction()` (so the rendered action is bound to its host), renders nothing when the name does not resolve or the action is hidden, and throws a `RuntimeException` when the enclosing `this` is not an [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) host.
An optional second parameter binds per-render arguments, `renderAction('delete', { id: 5 })`, through `HasMountableArguments`'s `__invoke()`, which Twig syntax cannot call directly: the bound instance is a clone, so the host-cached action stays argument-free, and the serialized `arguments` mount param reaches the trigger markup.
The order is resolve, bind, hidden-check, so a visibility closure can read the bound arguments.
[`ActionExtensionTest`](../../../packages/actions/tests/Integration/Twig/Extension/ActionExtensionTest.php) covers it through a fixture host template's `{{ renderAction('delete') }}`.

### Asset registration is an AssetMapper prepend

Filament's `packageBooted()` calls `FilamentAsset::register([...], 'filament/actions')` with a single `Js::make('actions', __DIR__ . '/../dist/index.js')`.
Sylament's translation is a [`prependExtension()`](../../../packages/actions/src/SylamentActionsBundle.php) mapping `packages/actions/assets/dist` to `@sylament/actions`, guarded by `isAssetMapperAvailable()`, the same shape and the same guard support's and notifications' carry, so a consumer without AssetMapper is not forced to install it.
The base [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php) supplies only that guard; each bundle writes its own prepend.

Without it the failure is loud rather than silent, which is worth knowing because it lands nowhere near a click.
StimulusBundle resolves a `controllers.json` entry by reading the PHP package's `assets/package.json` ([`UxPackageReader`](../../../vendor/symfony/stimulus-bundle/src/Ux/UxPackageReader.php) turns `@sylament/actions` back into the `sylament/actions` install path), then calls `assetMapper->getAssetFromSourcePath()` on `<packageDirectory>/` plus the controller's `main` and **throws** `Could not find an asset mapper path that points to the "…" controller` when the path sits outside every registered one ([`ControllersMapGenerator`](../../../vendor/symfony/stimulus-bundle/src/AssetMapper/ControllersMapGenerator.php)).
So an app that enables the controller without the prepend fails at controllers-map generation.
That is the call [`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) makes against the compiled container, asking the real `AssetMapperInterface` for the built controller by source path, which is why the test is written at the AssetMapper seam rather than against the raw config array.

### The asset workspace is JS-only, and its importmap block names only Stimulus

`packages/actions/assets` joins the pnpm workspace through [`pnpm-workspace.yaml`](../../../pnpm-workspace.yaml)'s `packages/*/assets` glob; its `tsconfig.json` and `scripts/build-js.mjs` are copies of notifications', identical **modulo each copy's own `BUNDLED_ENTRIES` table**, which is empty here, so every entry ships per-file and unbundled with its import specifiers verbatim.
Keeping the copies in lockstep is manual.
`@symfony/ux-live-component` is declared dev **and** peer at `^3.2.0`, a floor written in the **npm** half's version numbers, which the PHP package's bundled copy versions independently of (the installed v3.5.1 bundles JS 3.5.1, which the floor admits), and it stays a bare specifier in the built file, resolved by the consuming app's importmap.
Actions, forms, schemas, tables and panels declare the same dev-and-peer pair; notifications' workspace declares neither, because its toast controller [hands the emit to this one](#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).

No `symfony.importmap` entry is declared for it, and that is a decision rather than an omission, the same one notifications' workspace [records](../notifications/notifications-bundle.md#asset-registration-is-an-assetmapper-prepend-and-the-css-rides-supports-build).
The block names what a consumer's importmap must hold and might not otherwise get; here this package's own composer manifest requires `symfony/ux-live-component`, whose recipe installs the entry as a **path** to the vendored `live_controller.js`, and a version constraint beside it would name the CDN copy, a second artifact registering a second `live` controller.
The blast radius if the path entry is missing anyway is the whole controller, not one arm: `@symfony/ux-live-component` is a **top-level** import in the built `dispatch_controller.js`, so an importmap without it 404s the module before `afterLoad()` ever runs, and every dispatch on the page dies with it.

Its stylesheet is *not* built here either.
Support's `assets/src/styles/index.css` imports this package's [`src/styles/index.css`](../../../packages/actions/assets/src/styles/index.css) directly, so the `sy-ac-*` rules compile into support's single built stylesheet; `build` only emits the JS, and actions ships no CSS dist of its own.

### The dispatch controller is page-wide, and Filament has no counterpart for it

Filament needs no such controller: its trigger Blade emits `wire:click="$dispatch(…)"`, an inline JavaScript expression Livewire evaluates against whatever component encloses it.
The port has no arbitrary-JS seam and [does not want one](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding), so the templates stamp payload attributes and something else does the delivery.
[`dispatch_controller.ts`](../../../packages/actions/assets/src/controllers/dispatch_controller.ts) is that something, and it is deliberately *not* a per-element controller.

**It never binds to an element.**
Stimulus calls `static afterLoad()` once, at **registration** time, with the identifier and the application rather than a host element (the installed 3.2.2 declares `Controller.afterLoad(_identifier, _application)`), so a controller class can run setup for a page that contains none of its elements, and this one contains none by design, because no template emits `data-controller="sylament--actions--dispatch"`.
`fetch: eager` in the `symfony.controllers` block is what makes that fire at all: a lazy controller is registered only when a matching element appears, which for an element-less controller is never.
A module-level `listening` flag guards the one `document.addEventListener` against a double registration.

**The consuming app has to enable the package, and nothing warns it if it doesn't.**
`fetch: eager` decides *how* a controller registers; it has no say in *whether* the app considers this package at all.
That is decided by the app's own `assets/controllers.json`: [`ControllersMapGenerator::loadUxControllers()`](../../../vendor/symfony/stimulus-bundle/src/AssetMapper/ControllersMapGenerator.php) iterates the packages **listed in that file**, and `continue`s past any entry whose local `enabled` is false.
So the map needs

```json
"@sylament/actions": { "dispatch": { "enabled": true, "fetch": "eager" } }
```

and without it the controller is never registered, `afterLoad()` never runs, the document listener is never added, and **every** dispatch on the page is a silent no-op, including a toast's, which [delegates its emit](../notifications/notification.md#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload) to this controller.
Nothing throws, nothing logs, and the click still looks handled: the close arm keeps working, so a `dispatch()->close()` toast collapses exactly as it should while the event goes nowhere.
This is the mirror image of the [missing prepend](#asset-registration-is-an-assetmapper-prepend), which fails loudly at controllers-map generation: a consumer that gets the prepend but not the entry sees no error anywhere.

In a Flex app the entry writes itself, on a `composer` run rather than a cache clear.
`PackageJsonSynchronizer::updateControllersJsonFile()` walks the installed PHP packages, skips any whose composer manifest lacks the `symfony-ux` keyword (`packages/actions/composer.json` carries it, as support's and notifications' do), reads `assets/package.json`'s `symfony.controllers` block and copies each controller's declared `enabled`/`fetch` in as the default for a package it has not seen before, leaving an existing entry's `enabled` alone, so a consumer who turned it off stays off.
For the peer demo that means `composer update "sylament/*"` in `../demo`, not an edit.
The trap is that a **path**-repository consumer sees no new package boundary when the monorepo merely gains an asset workspace, so nothing triggers the sync until something in that package's composer metadata moves.

**It listens in the capture phase**, and that is the load-bearing choice.
A delegated listener on `document` in the bubble phase is at the mercy of every ancestor between the trigger and the root: support's section header calls `event.stopPropagation()` in [`toggleFromButton`](../../../packages/support/assets/src/controllers/section_controller.ts), and any `:stop` action option would do the same silently.
Capture runs top-down before all of them.
It also fixes the ordering `dispatch()->close()` depends on: the toast root's `handleActionClick` is a plain bubble-phase Stimulus action, so the emit is guaranteed to happen before the toast takes itself out of the DOM.

**The arms, in order.**
From `event.target` the handler walks `closest('[data-sylament-action-event]')`; a trigger with no event name is not a dispatch and is dropped.
It then reads the trigger's *nearest* live root once, `closest('[data-controller~="live"]')`, `null` when the trigger sits outside every live component, and, inside one `try`, takes exactly one of these:

1. `data-sylament-action-dispatch-self` present **and** a nearest root exists → `emitSelf(name, data)` on that root's component.
   This is what gives `dispatchSelf()` real self semantics: all five trigger templates [emit the marker](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding) whenever the direction is `'self'`.
   The read is a **presence** test, matching a marker that carries no value.
2. Anything else (no self marker, or a self marker with no live ancestor to be self of) → `emit(name, data, to)`, with `data-sylament-action-dispatch-to` passed as `emit()`'s third argument and `null` when absent or empty.
   The emitter is the nearest root when there is one and the page's **first** `[data-controller~="live"]` element otherwise; with neither, the dispatch is dropped.
3. Anything that rejects, such as `getComponent()` on an element the live application never registers, is **silent**.
   That matches a dispatch nobody listens to, and it is why the `catch` is empty but for its why-swallow comment.

Each root resolves through `resolveComponent()`: UX Live's registry, retried once after that element's `live:connect` when the registry is not ready.
That is the piece of schemas' [`utils/live_host.ts`](../../../packages/schemas/assets/src/utils/live_host.ts) this controller carries, minus the `closest()` half the arms need separately, inlined rather than imported, the same per-file-build trade [tables' copy](../tables/tables-bundle.md#the-assets-register-through-assetmapper-as-stimulus-controllers-not-as-alpine-components) records.
It diverges from both copies in one way, and the reason is where it runs: those two resolve once per controller connect, this one runs from a page-wide click listener, so an unbounded wait would leave one pending promise and one `live:connect` listener behind per click on a root that never connects.
The wait is bounded by a timeout that rejects into arm 3.

**What the nearest-root preference in arm 2 does and does not buy.**
It does not change delivery: `Component.emit()` routes through `performEmit(…, emitUp: false, …)` → `findComponents(this, false, matchingName)`, which iterates the whole component registry and skips the current-component filter entirely unless `onlyParents` is true (`findComponents()` in `vendor/symfony/ux-live-component/assets/dist/live_controller.js`), so a listener on a nested relation manager hears a `dispatch()` emitted from the outer page exactly as it hears one emitted from itself.
What it buys is a truthful emitter identity for anything that ever reads one, at no cost.
Arm 1 is where the nearest root is load-bearing.

**The page-first fallback in arm 2 is load-bearing.**
A toast is the live case: notifications' outlet is a plain `<div>` and panels' [`Document`](../../../packages/panels/templates/partials/Document/index.html.twig) renders it as a **sibling** of the layout that embeds the page component, so every action inside a toast has no live ancestor at all.
Dropping the fallback makes `dispatch()`, `dispatch()->close()` and a grouped `dispatchTo()` in a toast silent no-ops while the close half still fires: the toast collapses and the event goes nowhere, the same dark failure shape a missing `controllers.json` entry has above.
It is also the arm [`dispatchSelf()` in a toast](../notifications/notification.md#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload) falls through to.

**A double-click is two emits, and the controller leaves batching to UX Live.**
The controller does not debounce: each click emits, and every listener that hears the emit queues an action on its own component with a 1 ms debounce (`doEmit()` calls `this.action(action, data, 1)`).
Two emits inside that window ride one live request; an emit that lands while the component has a request in flight waits in `pendingActions` and goes out in the next request once the first completes (`tryStartingRequest()` and `performRequest()` in `vendor/symfony/ux-live-component/assets/dist/live_controller.js`).
Either way the listener runs once per click, which is what Livewire does with a double-clicked `$dispatch` too.
A listener that must not run twice guards itself; the controller does not try to tell a double-click from two intended clicks.

**Close is deliberately not handled here.**
`data-sylament-action-close` is a neutral marker meaning *this action closes its context*, and only the context knows what closing means: a toast removes itself, a modal dismisses.
So the close arm stays with whoever owns the click context, and this controller reads the attribute nowhere.
The split is the one the [payload attributes](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding) were designed around: templates stamp payload, context owners deliver behaviour, and dispatch is the one behaviour that has no context.

Only one context reads the marker, and the asymmetry is worth stating rather than assuming from the design.
The toast root's controller is its only reader in the repo; support's modal is served instead by the explicit `data-action="sylament--support--modal#dismiss"` token the **bare-close** arm emits beside the marker, and the dispatch arm emits no `data-action` at all, so a `dispatch()->close()` rendered inside a modal stamps a marker nothing reads, and the modal stays open.
A toast is the only place that pairing closes anything.
Closing the gap is a modal-side read of the marker, in `support`, not a change here.

### Every package declares what its own source needs, transitives notwithstanding

The rule and its omissions are in [`.claude/rules/manifests.md`](../../../.claude/rules/manifests.md) and [`docs/internals/dependencies.md`](../../internals/dependencies.md#the-transitive-guarantees-behind-the-omissions); this entry records how they land on `sylament/actions`.

- `symfony/ux-twig-component`: [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) type-hints `AsTwigComponent` and `ComponentStack`, so `actions` declares it rather than leaning on `support` to keep providing it.
- `symfony/ux-live-component`: [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) uses `ComponentToolsTrait` and `#[LiveProp]` / `#[LiveAction]`.
- `symfony/string`: [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) imports `u()`.
- `symfony/service-contracts` and `symfony/event-dispatcher-contracts`: the `#[Required]` setters and [`HasLifecycleHooks`](../../../packages/actions/src/Concerns/HasLifecycleHooks.php)' dispatcher.
- `doctrine/orm`: `Action` and the record actions import `Doctrine\ORM` classes (`QueryBuilder`, `EntityManagerInterface`) directly, at the `^3.7` constraint `schemas`, `forms`, `tables` and `panels` carry.
- `sylament/schemas`: [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) type-hints `Schemas\Components\Component` and `Schemas\Schema`, and `schemas` requires `sylament/actions` back ([schemas-bundle.md](../schemas/schemas-bundle.md#no-test-pins-the-sylamentactions-require)).
  Filament carries the same cycle: `filament/schemas` requires `filament/actions` directly, and `filament/actions` reaches `filament/schemas` only through `filament/forms`; the port states the edge directly.
- `sylament/forms`: [`AttachAction::getRecordSelect()`](attach-action.md#the-record-select-is-a-forms-select-which-is-why-actions-requires-forms) and [`AssociateAction`'s](attach-action.md#the-record-select-is-a-forms-select-which-is-why-actions-requires-forms) build a `Sylament\Forms\Components\Select` and **return** it, so no `instanceof` can stand in front of it.
  `forms` requires `actions` back for its own field-level actions, the edge Filament's manifests carry too (`filament/actions` requires `filament/forms`, and `filament/forms` requires `filament/actions`).

Two integrations stay a `suggest` behind a guard.
`sylament/tables`: `HasActions` names tables' [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) in `src/` behind an `instanceof` guard, so the branch is dead in an install without the package ([has-actions.md](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference)); `filament/actions` does not require `filament/tables` either.
`sylament/notifications`: [`CanNotify`](can-notify.md#the-notification-arm-is-a-guarded-soft-integration-not-a-require) sends a real toast behind a `class_exists()` guard, because `sylament/notifications` requires `sylament/actions` (its notifications carry `Action`s) and a require back would close a composer cycle; Filament's `filament/actions` requires `filament/notifications` outright.
[`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php), in the suite that has both packages, keeps that reference honest.

The monorepo resolves every package out of one root `vendor/`, so dropping a require would fail nothing here and break only the split repository.
[`ManifestTest`](../../../packages/actions/tests/Architecture/ManifestTest.php) therefore reads `composer.json` and asserts the three `sylament/*` requires and the two suggestions directly.

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers `Testable::mixin(new TestsActions)`, a Livewire `Testable` mixin (`mountAction()`, `callAction()`, `assertActionExists()`, …) whose every method calls into `$this->instance()` (a Livewire component) through `$this->call()`/`$this->set()`.
Sylament tests are Pest/`KernelTestCase`, not Livewire `Testable`; the Integration suite renders `Action` directly through `ViewComponent::render(Environment $twig)` ([`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php)) rather than driving a mounted host component's action lifecycle through assertion helpers.
This is a deliberate non-goal, **skipped**, for the same reason forms' `TestsForms`/`TestsFormComponentActions` mixins are skipped ([forms-bundle.md](../forms/forms-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported)).
`TestsActions` and its `TestAction` helper have their own rows in the [package README](README.md).
