---
description: The actions bundle class. (draft)
ports:
  - vendor/filament/actions/src/ActionsServiceProvider.php
  - vendor/filament/actions/routes/web.php
  - vendor/filament/actions/database/migrations/
owns:
  - packages/actions/assets/src/controllers/
  - packages/actions/src/SylamentActionsBundle.php
---
# SylamentActionsBundle (actions)

[`Sylament\Actions\SylamentActionsBundle`](../../../packages/actions/src/SylamentActionsBundle.php) — the actions bundle class.
It ports Filament's [`ActionsServiceProvider`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) (Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle` via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)).
Every host page that mounts actions, and every `Action` instance itself, depends on this bundle only indirectly — through the `@SylamentActions` Twig namespace its registration makes available to `Action::$template` ([`Action.php`](../../../packages/actions/src/Action.php)) and to the modal partial.
Most of what the bundle does is still convention rather than code: it inherits the `@SylamentActions` Twig namespace by AbstractBundle/TwigBundle convention, and `Action` and its modal render through support's/schemas' `ViewComponent::render()` with nothing to wire.
Its two `loadExtension()` responsibilities have no Filament counterpart — `ActionsServiceProvider` binds no container services: the host-side [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) component is a **class**-based Twig component, so it must be registered and tagged `twig.component`, and the [`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php) supplying the `renderAction()` template helper is registered and tagged `twig.extension` (see their rows).
A third responsibility runs before those, in `prependExtension()`: mapping `assets/dist` into AssetMapper as `@sylament/actions`, which is what makes the built dispatch controller reachable at all.
The `filament.actions` middleware group, the export/import routes and migrations, the `Make*Exporter`/`Make*Importer` commands, translations, and the `TestsActions` `Testable` mixin are not ported yet — all of them either serve the unbuilt Exports/Imports subsystem or are Livewire-specific.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle registration + `@SylamentActions` view namespace](../../../packages/actions/src/SylamentActionsBundle.php) | [`->hasViews()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [Twig component service registration](../../../packages/actions/src/SylamentActionsBundle.php) (`loadExtension()`) | — (new; Filament's Blade components need no service) | done | — | [diverges](#the-bundle-registers-a-twig-component-service-which-filament-has-no-counterpart-for) |
| [Twig extension service registration](../../../packages/actions/src/SylamentActionsBundle.php) (`loadExtension()`, `renderAction()`) | — (Sylament-only; mirrors schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | — | [diverges](#the-bundle-registers-a-renderaction-twig-extension-which-filament-has-no-counterpart-for) |
| [`@sylament/actions` asset workspace](../../../packages/actions/assets/package.json) | — (new; Filament ships a prebuilt `dist/index.js` and no workspace) | done | — | [diverges](#the-asset-workspace-is-js-only-and-its-importmap-block-names-only-stimulus) |
| [Page-wide dispatch controller](../../../packages/actions/assets/src/controllers/dispatch_controller.ts) | — (Sylament-only; Filament dispatches from an inline `wire:click` expression) | done | — | [diverges](#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it) |
| [Asset registration — the `@sylament/actions` AssetMapper prepend](../../../packages/actions/src/SylamentActionsBundle.php) (`Js::make('actions', dist/index.js)`) | [`packageBooted()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | done | — | [diverges](#asset-registration-is-an-assetmapper-prepend) |
| Exports/Imports provider wiring (`hasMigrations`, `hasRoutes('web')`, `filament.actions` middleware group, `hasCommands`) | [`configurePackage()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php), [`packageRegistered()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | blocked | Exports/Imports subsystem | [detail](#exportsimports-provider-wiring) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | todo | — | [detail](#translations) |
| `Testable::mixin(new TestsActions)` | [`packageBooted()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

The bundle-load + view-namespace row is covered indirectly: [`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php), [`ButtonRenderTest`](../../../packages/actions/tests/Integration/Components/ButtonRenderTest.php), [`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php), [`ModalRenderTest`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php), and the `Concerns/HasActions*` Integration tests all boot [`tests/Fixtures/Kernel.php`](../../../packages/actions/tests/Fixtures/Kernel.php) (which registers `SylamentActionsBundle`) and render `Action` through `ViewComponent::render()`, proving the bundle loads and its `@SylamentActions/partials/Action/Button.html.twig` / `@SylamentActions/components/ActionModal/index.html.twig` templates resolve.
[`ModalsRenderTest`](../../../packages/actions/tests/Integration/Components/ModalsRenderTest.php) covers the Twig-component registration row: `<twig:SylamentActions:Modals />` only resolves if the service was registered and tagged.
[`ActionExtensionTest`](../../../packages/actions/tests/Integration/Twig/Extension/ActionExtensionTest.php) covers the Twig-extension row the same way: its fixture host template's `{{ renderAction('delete') }}` only renders if the service was registered and tagged `twig.extension`.
[`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) covers the asset-registration row against the compiled container: it asks the real `AssetMapperInterface` for the built dispatch controller by source path and requires the logical path back, so a missing or misaliased prepend fails it.
Its second and fourth cases pin the explicit `->set()` registration list — the second `AttachAction`, `BulkAction`, `BulkActionGroup`, `DetachAction`, `DetachBulkAction`, `ReplicateAction` and `ViewAction`, the fourth the associate family (`AssociateAction`, `DissociateAction`, `DissociateBulkAction`) — because one the bundle does not name falls back to `new $class()` in [`ComponentResolver::instantiate()`](../support/component.md), where every `#[Required]` setter on it (the manager registry a write body persists through, the event dispatcher) silently never runs and nothing errors.
The six the bundle also `->set()`s — `Action`, `ActionGroup`, `CreateAction`, `DeleteAction`, `DeleteBulkAction` and `EditAction` — are named by neither case, a standing gap rather than a decision.
A new prebuilt action therefore lands with a line in `configureComponents()` and a name in one of those cases, in the commit that creates it.
That is the shape [support's](../support/support-bundle.md#asset-registration) and [notifications'](../notifications/notifications-bundle.md#asset-registration) identical rows still want, and it is written once here to be copied — both point back at it.
It also covers the Doctrine rig the suite grew for the record actions — [`tests/Fixtures/services_doctrine.php`](../../../packages/actions/tests/Fixtures/services_doctrine.php) registering an in-memory SQLite `EntityManagerInterface` and the single-manager [`FixtureManagerRegistry`](../../../packages/actions/tests/Fixtures/Doctrine/FixtureManagerRegistry.php) behind `ManagerRegistry` — beside `SylamentNotificationsBundle` and a mock-file session on the fixture Kernel, because a record action's write body persists through the registry and sends a titled toast, and `Notification::send()` throws on an instance the container did not build.
The unported responsibilities have no coverage because they have no code.

## Details

### Exports/Imports provider wiring

Four `configurePackage()`/`packageRegistered()` calls exist solely to support Filament's background export/import feature, which has no Sylament port at all (the `actions` [package README](README.md) lists `Exporter`, `Importer`, `ExportAction`, `ImportAction`, and the whole `Exports/`/`Imports/`/`Commands/` subtrees as `todo`):

- `->hasMigrations(['create_imports_table', 'create_exports_table', 'create_failed_import_rows_table'])` — Eloquent migrations for the [`Export`](../../../vendor/filament/actions/src/Exports/Models/Export.php) / [`Import`](../../../vendor/filament/actions/src/Imports/Models/Import.php) / `FailedImportRow` models.
  No Doctrine entity or migration equivalent exists.
- `->hasRoutes('web')` registers [`routes/web.php`](../../../vendor/filament/actions/routes/web.php): two `Route::get()`s (`DownloadExport`, `DownloadImportFailureCsv`) under a `filament.actions`-middleware, `filament`-prefixed group.
  No Symfony route equivalent exists.
- `packageRegistered()`'s `Router::middlewareGroup('filament.actions', ['web'])` exists only to gate the two routes above.
- `->hasCommands([MakeExporterCommand::class, MakeImporterCommand::class])` — stub-publishing `make:` commands for user `Exporter`/`Importer` classes.
  No `Commands/` subtree exists in the port (tracked as the `Commands` and `Commands / FileGenerators` `todo` rows in the [package README](README.md)).

**Next step:** none of this can be ported before the Exports/Imports subsystem itself lands — the migrations describe tables the port has no models for, the routes serve controllers the port doesn't have, and the commands generate classes (`Exporter`/`Importer`) the port doesn't define.
All four are **blocked** on that subsystem, not skipped: background export/import is a wanted parity feature, just not started.
Filament runs those jobs on Laravel's queue; when the subsystem lands, they run as Symfony Messenger messages.

### Translations

`->hasTranslations()` registers Filament's `resources/lang` namespace (labels for the confirmation modal, notification titles, and generated import/export messages).
Unported.
**Next step:** port when the translations subtree is tackled — independent of the Exports/Imports blocker above, since the confirmation-modal strings apply to the already-ported `Action`/`CanRequireConfirmation`.

## Divergences

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace.
Sylament registers nothing in the bundle file (its body is a bare `//`): `AbstractBundle::getPath()` returns `packages/actions`, and TwigBundle auto-maps `packages/actions/templates` → `@SylamentActions`.
The wiring is therefore invisible in the bundle — intentional, and the reason [`Action`](../../../packages/actions/src/Action.php) can default `$defaultTemplate` to `self::BUTTON_TEMPLATE` (`'@SylamentActions/partials/Action/Button.html.twig'`) with no explicit twig path config.
Same divergence as [forms-bundle.md](../forms/forms-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) and [schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).

### The bundle registers a Twig component service, which Filament has no counterpart for

Filament's Blade components need no service registration at all — Blade resolves `<x-filament-actions::modals />` from the view namespace.
Sylament's [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) is a class, so it has to be a container service tagged `twig.component`.
Two choices in [`loadExtension()`](../../../packages/actions/src/SylamentActionsBundle.php) are worth knowing:

- `->autoconfigure()` lets the `#[AsTwigComponent]` attribute supply the tag (ux-twig-component registers it for autoconfiguration), keeping the attribute the single source of truth for the component's name and template instead of duplicating both as tag attributes here.
- The component stack is injected by **service id** (`ux.twig_component.component_stack`), not by type: ux-twig-component registers no class alias for it, so autowiring would fail.

Registration is unconditional, unlike [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php)'s optional-dependency guard, because `symfony/ux-twig-component` is a hard `require` of this package — see [the dependency rule](#every-package-declares-what-its-own-source-needs-transitives-notwithstanding).

### The bundle registers a `renderAction()` Twig extension, which Filament has no counterpart for

Filament host templates render an action by echoing the object itself — `Action` extends a `ViewComponent` that implements `Htmlable` ([`ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)), so Blade renders `{{ $action }}` via `toHtml()` and the provider wires nothing.
Twig has no equivalent implicit-render path, so the bundle registers [`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php) (`sylament_actions.action_extension`, tagged `twig.extension`) supplying `renderAction(name)` — the actions twin of schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php), with the wiring mirrored deliberately: support's `sylament_support.component_extension` is constructor-injected by service id, and the function registers with `needs_environment` + `needs_context`.
The helper resolves the name through the **surrounding live component's** `getAction()` (so the rendered action is bound to its host), renders nothing when the name does not resolve or the action is hidden, and throws a `RuntimeException` when the enclosing `this` is not an [`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php) host.
An optional second parameter binds per-render arguments — `renderAction('delete', { id: 5 })` — through `HasMountableArguments`'s `__invoke()`, which Twig syntax cannot call directly: the bound instance is a clone, so the host-cached action stays argument-free, and the serialized `arguments` mount param reaches the trigger markup.
The order is resolve → bind → hidden-check, so a visibility closure can read the bound arguments.

### Asset registration is an AssetMapper prepend

Filament's `packageBooted()` calls `FilamentAsset::register([...], 'filament/actions')` with a single `Js::make('actions', __DIR__ . '/../dist/index.js')` (the actions Alpine bundle: modal open/close, confirmation wiring).
Sylament's translation is a [`prependExtension()`](../../../packages/actions/src/SylamentActionsBundle.php) mapping `packages/actions/assets/dist` to `@sylament/actions`, guarded by `isAssetMapperAvailable()` — the same shape and the same guard support's and notifications' carry, so a consumer without AssetMapper is not forced to install it.
The base [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php) supplies only that guard; each bundle writes its own prepend.

Without it the failure is loud rather than silent, which is worth knowing because it lands nowhere near a click.
StimulusBundle resolves a `controllers.json` entry by reading the PHP package's `assets/package.json` ([`UxPackageReader`](../../../vendor/symfony/stimulus-bundle/src/Ux/UxPackageReader.php) turns `@sylament/actions` back into the `sylament/actions` install path), then calls `assetMapper->getAssetFromSourcePath()` on `<packageDirectory>/` plus the controller's `main` and **throws** `Could not find an asset mapper path that points to the "…" controller` when the path sits outside every registered one ([`ControllersMapGenerator`](../../../vendor/symfony/stimulus-bundle/src/AssetMapper/ControllersMapGenerator.php)).
So an app that enables the controller without the prepend fails at controllers-map generation.
That is exactly the call [`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) makes, which is why the test is written at the AssetMapper seam rather than against the raw config array.

### The asset workspace is JS-only, and its importmap block names only Stimulus

`packages/actions/assets` joins the pnpm workspace through [`pnpm-workspace.yaml`](../../../pnpm-workspace.yaml)'s `packages/*/assets` glob the moment it gains a `package.json`; its `tsconfig.json` and `scripts/build-js.mjs` are copies of notifications', identical **modulo each copy's own `BUNDLED_ENTRIES` table** — empty here, where support's bundles `@floating-ui/dom` into the dropdown controller and `sortablejs` into the sortable one, and forms' bundles both of them into the select one, its `license` a list and its `inlines` naming the engine module — so every entry ships per-file and unbundled with its import specifiers verbatim.
Keeping the copies in lockstep is manual, and the searchable select shows what that costs in practice: `licenseBanner()` had to change in all five at once, learning to trim each `LICENSE` line before matching the copyright (for Tom Select's indented notice at the time), and again for the select engine swap, when `licenseBanner()` learned to take a list and `inlines` was added.
`@symfony/ux-live-component` is declared dev **and** peer at `^3.2.0` — a floor written in the **npm** half's version numbers, which the PHP package's bundled copy versions independently of (the installed v3.5.1 bundles JS 3.5.1, which the floor admits), so the constraint tracks the JS line rather than the PHP release — and stays a bare specifier in the built file, resolved by the consuming app's importmap.
Five workspaces declare the same dev-and-peer pair at the same version — actions, forms (for its [select controller](../forms/forms-bundle.md#the-select-controller-binds-a-port-of-filaments-select-utility-to-the-live-host), which resolves its host through the same `getComponent()` seam this one does), schemas, tables and panels; notifications' is the one without it, having dropped both declarations when its toast controller [handed the emit over](#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).

No `symfony.importmap` entry is declared for it, and that is a decision rather than an omission — the same one notifications' workspace [records](../notifications/notifications-bundle.md#asset-registration-is-an-assetmapper-prepend-and-the-css-rides-supports-build).
The block names what a consumer's importmap must hold and might not otherwise get; here this package's own composer manifest requires `symfony/ux-live-component`, whose recipe installs the entry as a **path** to the vendored `live_controller.js`, and a version constraint beside it would name the CDN copy — a second artifact registering a second `live` controller.
The blast radius if the path entry is missing anyway is the whole controller, not one arm: `@symfony/ux-live-component` is a **top-level** import in the built `dispatch_controller.js`, so an importmap without it 404s the module before `afterLoad()` ever runs, and every dispatch on the page dies with it.

Its stylesheet is *not* built here either.
Support's `assets/src/styles/index.css` imports this package's [`src/styles/index.css`](../../../packages/actions/assets/src/styles/index.css) directly, so the `sy-ac-*` rules compile into support's single built stylesheet; `build` only ever emits the JS, and actions ships no CSS dist of its own.

### The dispatch controller is page-wide, and Filament has no counterpart for it

Filament needs no such controller: its trigger Blade emits `wire:click="$dispatch(…)"`, an inline JavaScript expression Livewire evaluates against whatever component encloses it.
The port has no arbitrary-JS seam and [does not want one](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding), so the templates stamp payload attributes and something else does the delivery.
[`dispatch_controller.ts`](../../../packages/actions/assets/src/controllers/dispatch_controller.ts) is that something, and it is deliberately *not* a per-element controller.

**It never binds to an element.**
Stimulus calls `static afterLoad()` once, at **registration** time, with the identifier and the application rather than a host element (verified against the installed 3.2.2 `Controller.afterLoad(_identifier, _application)`), so a controller class can run setup for a page that contains none of its elements — and this one contains none by design, since no template emits `data-controller="sylament--actions--dispatch"`.
`fetch: eager` in the `symfony.controllers` block is what makes that fire at all: a lazy controller is registered only when a matching element appears, which for an element-less controller is never.
A module-level `listening` flag guards the one `document.addEventListener` against a double registration.

**The consuming app has to enable the package, and nothing warns it if it doesn't.**
`fetch: eager` decides *how* a controller registers; it has no say in *whether* the app considers this package at all.
That is decided by the app's own `assets/controllers.json`: [`ControllersMapGenerator::loadUxControllers()`](../../../vendor/symfony/stimulus-bundle/src/AssetMapper/ControllersMapGenerator.php) iterates the packages **listed in that file**, and `continue`s past any entry whose local `enabled` is false.
So the map needs

```json
"@sylament/actions": { "dispatch": { "enabled": true, "fetch": "eager" } }
```

and without it the controller is never registered, `afterLoad()` never runs, the document listener is never added, and **every** dispatch on the page is a silent no-op — including a toast's, whose local emit [was removed](../notifications/notification.md#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload) in favour of this one.
Nothing throws, nothing logs, and the click still looks handled: the close arm keeps working, so a `dispatch()->close()` toast collapses exactly as it should while the event goes nowhere.
This is the mirror image of the [missing prepend](#asset-registration-is-an-assetmapper-prepend), which fails loudly at controllers-map generation — a consumer that gets the prepend but not the entry sees no error anywhere.

In a Flex app the entry writes itself, on a `composer` run rather than a cache clear.
`PackageJsonSynchronizer::updateControllersJsonFile()` walks the installed PHP packages, skips any whose composer manifest lacks the `symfony-ux` keyword (`packages/actions/composer.json` carries it, as support's and notifications' do), reads `assets/package.json`'s `symfony.controllers` block and copies each controller's declared `enabled`/`fetch` in as the default for a package it has not seen before — leaving an existing entry's `enabled` alone, so a consumer who turned it off stays off.
For the peer demo that means `composer update "sylament/*"` in `../demo`, not an edit.
The trap is that a **path**-repository consumer sees no new package boundary when the monorepo merely gains an asset workspace, so nothing triggers the sync until something in that package's composer metadata moves.

**It listens in the capture phase**, and that is the load-bearing choice.
A delegated listener on `document` in the bubble phase is at the mercy of every ancestor between the trigger and the root: support's section header already calls `event.stopPropagation()` in [`toggleFromButton`](../../../packages/support/assets/src/controllers/section_controller.ts), and any future `:stop` action option would do the same silently.
Capture runs top-down before all of them.
It also fixes the ordering `dispatch()->close()` depends on — the toast root's `handleActionClick` is a plain bubble-phase Stimulus action, so the emit is guaranteed to happen before the toast takes itself out of the DOM.

**The arms, in order.**
From `event.target` the handler walks `closest('[data-sylament-action-event]')`; a trigger with no event name is not a dispatch and is dropped.
It then reads the trigger's *nearest* live root once — `closest('[data-controller~="live"]')`, `null` when the trigger sits outside every live component — and, inside one `try`, takes exactly one of these:

1. `data-sylament-action-dispatch-self` present **and** a nearest root exists → `emitSelf(name, data)` on that root's component.
   This is what gives `dispatchSelf()` real self semantics: all five trigger templates [emit the marker](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding) whenever the direction is `'self'`.
   The read is a **presence** test, matching a marker that carries no value.
2. Anything else — no self marker, or a self marker with no live ancestor to be self of — → `emit(name, data, to)`, with `data-sylament-action-dispatch-to` passed as `emit()`'s third argument and `null` when absent or empty.
   The emitter is the nearest root when there is one and the page's **first** `[data-controller~="live"]` element otherwise; with neither, the dispatch is dropped.
3. Anything that rejects — `getComponent()` on an element the live application never registers — is **silent**.
   That matches a dispatch nobody listens to, and it is why the `catch` is empty but for its why-swallow comment.

Each root resolves through `resolveComponent()`: UX Live's registry, retried once after that element's `live:connect` when the registry is not ready yet.
That is the piece of schemas' [`utils/live_host.ts`](../../../packages/schemas/assets/src/utils/live_host.ts) this controller carries, minus the `closest()` half the arms need separately — inlined rather than imported, the same per-file-build trade [tables' copy](../tables/tables-bundle.md#asset-registration) records.
It diverges from both copies in one way, and the reason is where it runs: those two resolve once per controller connect, this one runs from a page-wide click listener, so an unbounded wait would leave one pending promise and one `live:connect` listener behind per click on a root that never connects.
The wait is bounded by a timeout that rejects into arm 3.

**What the nearest-root preference in arm 2 does and does not buy.**
It does not change delivery: `Component.emit()` routes through `performEmit(…, emitUp: false, …)` → `findComponents(this, false, matchingName)`, which iterates the whole component registry and skips the current-component filter entirely unless `onlyParents` is true (`findComponents()` in `vendor/symfony/ux-live-component/assets/dist/live_controller.js`), so a listener on a nested relation manager hears a `dispatch()` emitted from the outer page exactly as it hears one emitted from itself.
What it buys is a truthful emitter identity for anything that ever reads one, at no cost.
Arm 1 is where the nearest root is load-bearing, and it resolved that way before this controller grew the retry — the change there is the retry, not the lookup.

**The page-first fallback in arm 2 is load-bearing, not a leftover.**
A toast is the live case: notifications' outlet is a plain `<div>` and panels' [`Document`](../../../packages/panels/templates/partials/Document/index.html.twig) renders it as a **sibling** of the layout that embeds the page component, so every action inside a toast has no live ancestor at all.
Dropping the fallback makes `dispatch()`, `dispatch()->close()` and a grouped `dispatchTo()` in a toast silent no-ops while the close half still fires — the toast collapses and the event goes nowhere, the same dark failure shape a missing `controllers.json` entry has above.
It is also the arm [`dispatchSelf()` in a toast](../notifications/notification.md#the-toast-root-owns-the-click-and-the-dispatch-arm-carries-only-payload) falls through to.

**A double-click is two emits, and the controller leaves batching to UX Live.**
The controller does not debounce: each click emits, and every listener that hears the emit queues an action on its own component with a 1 ms debounce (`doEmit()` calls `this.action(action, data, 1)`).
Two emits inside that window ride one live request; an emit that lands while the component already has a request in flight waits in `pendingActions` and goes out in the next request once the first completes (`tryStartingRequest()` and `performRequest()` in `vendor/symfony/ux-live-component/assets/dist/live_controller.js`).
Either way the listener runs once per click, which is what Livewire does with a double-clicked `$dispatch` too.
A listener that must not run twice guards itself; the controller does not try to tell a double-click from two intended clicks.

**Close is deliberately not handled here.**
`data-sylament-action-close` is a neutral marker meaning *this action closes its context*, and only the context knows what closing means — a toast removes itself, a modal dismisses.
So the close arm stays with whoever owns the click context and this controller reads the attribute nowhere.
The split is the same one the [payload attributes](can-dispatch-event.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding) were designed around: templates stamp payload, context owners deliver behaviour, and dispatch is the one behaviour that has no context.

Exactly **one** context implements the marker today, and the asymmetry is worth stating rather than assuming from the design.
The toast root's controller is its only reader in the repo; support's modal is served instead by the explicit `data-action="sylament--support--modal#dismiss"` token the **bare-close** arm emits beside the marker, and the dispatch arm emits no `data-action` at all — so a `dispatch()->close()` rendered inside a modal stamps a marker nothing reads, and the modal stays open.
A toast is the only place that pairing closes anything.
Closing the gap is a modal-side read of the marker, in `support`, not a change here.

### Every package declares what its own source needs, transitives notwithstanding

`symfony/ux-twig-component` is required by **both** `support` and `actions`, for two independent reasons, and neither is redundant:

- `support` ships the six anonymous Twig components, and every one of their templates opens with `{% props %}` — a token parser that **only** ux-twig-component provides.
  Without it those templates do not merely fail to render, they fail to parse.
  It was a `suggest` until this change; that was wrong, since a package whose shipped artifacts cannot be compiled without a dependency has a hard one.
- `actions` imports the namespace as real PHP symbols — [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) type-hints `AsTwigComponent` and `ComponentStack` — so it declares it directly rather than leaning on `support` to keep providing it.

The rule the monorepo follows: **declare what your own `src/` and `templates/` need.**
Inheriting a dependency through a sibling is how the split repos silently break — this audit found `actions` declaring neither `symfony/ux-live-component`, though [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) does `use ComponentToolsTrait` (a hard compile-time requirement) plus `#[LiveProp]` / `#[LiveAction]`, nor `symfony/string`, which [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) imports for `u()`.
Until both were declared the split `sylament/actions` repo could not load `HasActions` at all; it worked in the monorepo only because `sylament/schemas` happens to require `symfony/ux-live-component` at the root.

Those two stay on `actions` and do **not** move up to `support`: support's `src/` references `Symfony\UX\LiveComponent` nowhere, none of its templates call `live_action()`, and nothing in it uses `symfony/string`.
Pushing them down to the base package would have it declare dependencies it does not use — the same defect in reverse.

The same audit swept all nine packages.
It added `symfony/ux-twig-component` and `symfony/string` to `forms` (its `FieldWrapper` template opens with `{% props %}` and [`Field`](../../../packages/forms/src/Components/Field.php) imports `u()`), and found the five placeholder bundles — `infolists`, `notifications`, `panels`, `tables`, `widgets` — import nothing but `SylamentBundle`, so `sylament/support` alone is already complete for them.
The rule adds nothing to a package that uses nothing.
`twig/twig` and the ambient `symfony/dependency-injection` / `symfony/http-kernel` omissions are deliberate and recorded in [`docs/internals/dependencies.md`](../../internals/dependencies.md#the-transitive-guarantees-behind-the-omissions).

The schemas↔actions integration adds the rule's first genuinely mutual edge: [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php) type-hints `Schemas\Components\Component` and `Schemas\Schema` directly, so `actions` now requires `sylament/schemas`.
`schemas` requires `sylament/actions` back in the same commit, ahead of the schemas-side code (the component-level `HasActions` registration trait, `CanModifyActions`, the `HasComponents` child-schema unions, and everything the rest of the integration builds on top) that goes on to import `Action`/`ActionGroup` throughout the schemas↔actions integration — see [schemas-bundle.md](../schemas/schemas-bundle.md#sylamentactions-becomes-a-hard-composer-requirement).
Filament already carries this cycle: `filament/schemas`'s composer.json requires `filament/actions` directly, and `filament/actions` reaches `filament/schemas` back only transitively, through `filament/forms` (which requires both).
Sylament states the edge the two packages already share in Filament's graph directly, rather than through three packages — `forms`, `infolists`, `notifications` — that don't provide it here yet.

The tables↔actions seam is the counter-case, and it is where the rule's *own* boundary sits: `HasActions` names tables' [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) in `src/`, yet `actions` requires `sylament/tables` nowhere.
The reference is never unconditional — an `instanceof` guard stands in front of every use, and the branch behind it is simply dead in an install without the package — so it earns a composer `suggest` and the guard rather than a `require`, the same shape support's `isAssetMapperAvailable()` has carried since the beginning.
Reasoning and the test that keeps the soft reference honest: [has-actions.md](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference).

The prebuilt [`DeleteAction`](delete-action.md) adds two more requires, both by the same rule.
`symfony/service-contracts` is the plain case: the class imports `Symfony\Contracts\Service\Attribute\Required` for its manager-registry setter, so the package that ships the import declares it (`schemas` and `notifications` already do for the same attribute; `forms` uses it and does **not** declare it — an open gap on that package, not a precedent).
`doctrine/orm` is the judgment call: the symbol actually imported is `Doctrine\Persistence\ManagerRegistry`, which lives in `doctrine/persistence`, so a minimal declaration would name that package instead.
The port declares `doctrine/orm: ^3.7` — the constraint `schemas`, `forms`, `tables` and `panels` already carry — because the ORM hard-requires `doctrine/persistence` (`^3.3.1 || ^4`), so the interface cannot leave the graph, and because a one-package-per-Doctrine-constraint monorepo is what the [`docs/internals/dependencies.md` omission list](../../internals/dependencies.md#the-transitive-guarantees-behind-the-omissions) already reasons from (`doctrine/dbal` is undeclared everywhere on exactly this transitive-guarantee argument).
Revisit if a split `sylament/actions` is ever consumed by an application that wants Doctrine's persistence contracts without the ORM.

`sylament/forms` is where the rule cuts the other way, and the [six relationship actions](README.md#components) are what force it.
[`AttachAction::getRecordSelect()`](attach-action.md#the-record-select-is-a-forms-select-which-is-why-actions-requires-forms) and [`AssociateAction`'s](associate-action.md#the-record-select-is-a-forms-select-which-is-why-actions-needs-forms) build a `Sylament\Forms\Components\Select` and **return** it, so the class is resolved on every mount of either modal and no `instanceof` could stand in front of it: `actions` requires `sylament/forms`.
That closes the rule's second declared cycle, `forms` requiring `actions` back for its own field-level actions, and it is the edge Filament's manifests already carry (`filament/actions` requires `filament/forms`, and `filament/forms` requires `filament/actions`).
The soft link and the hard one need the same kind of pin for opposite reasons — actions' Unit tests do build the `Select` (`AttachActionTest` and `AssociateActionTest` both assert its shape), but the monorepo resolves every package out of one root `vendor/`, so the class autoloads whether or not actions' own manifest names it: dropping that line would fail nothing here and would break only the split repo.
[`ManifestTest`](../../../packages/actions/tests/Architecture/ManifestTest.php) reads `composer.json` instead, asserting the three hard requires and the two suggestions directly.

The notifications seam is the rule's **second** guarded soft integration, and the first one forced by a cycle rather than by weight.
[`CanNotify`](can-notify.md#the-notification-arm-is-a-guarded-soft-integration-not-a-require) imports `Sylament\Notifications\Notification` in `src/` and sends a real toast from `sendSuccessNotification()` / `sendFailureNotification()` — yet `sylament/actions` requires `sylament/notifications` **nowhere**, because `sylament/notifications` requires `sylament/actions` (its notifications carry `Action`s), and a require back would close a composer cycle.
Filament has no such problem: `filament/actions` requires `filament/notifications`, and `filament/notifications` reaches actions only transitively.
So the port takes the shape this section already sanctions for tables — a `class_exists()` guard in front of every use (the `::class` constant autoloads nothing, so an install without the package never resolves the symbol), a composer `suggest` entry, and a test in the suite that has both packages to keep the reference honest: [`CanNotifyTest`](../../../packages/notifications/tests/Integration/CanNotifyTest.php) in the notifications Integration suite.

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers `Testable::mixin(new TestsActions)` — a Livewire `Testable` mixin (`mountAction()`, `callAction()`, `assertActionExists()`, …) whose every method calls into `$this->instance()` (a Livewire component) via `$this->call()`/`$this->set()`.
Sylament tests are Pest/`KernelTestCase`, not Livewire `Testable`; the Integration suite renders `Action` directly through `ViewComponent::render(Environment $twig)` ([`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php)) rather than driving a mounted host component's action lifecycle through assertion helpers.
This is a deliberate non-goal — **skipped** — for the same reason forms' `TestsForms`/`TestsFormComponentActions` mixins are skipped ([forms-bundle.md](../forms/forms-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported)).
`TestsActions` and its `TestAction` helper are tracked as their own `todo` rows in the [package README](README.md) supporting surface.

_The `ActionsServiceProvider` → `SylamentActionsBundle` swap (and the underlying Spatie `PackageServiceProvider` → Symfony `AbstractBundle` move) is a systematic Symfony-idiom translation, like those the [rename table](../renames.md) collects under "Not renames" — Laravel package tooling with no per-name Sylament equivalent to enumerate — not a per-item rename or divergence.
The `InteractsWithActions`/`HasActions` names this file's neighbors use for the host contract and trait are the recorded [Has\*/InteractsWith\* swap](../renames.md#interface-and-trait-names-are-swapped), referenced here rather than re-derived._
