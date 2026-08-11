# SylamentActionsBundle (actions)

[`Sylament\Actions\SylamentActionsBundle`](../../../packages/actions/src/SylamentActionsBundle.php)
— the actions bundle class. It ports Filament's
[`ActionsServiceProvider`](../../../vendor/filament/actions/src/ActionsServiceProvider.php)
(Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle`
via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)). Every
host page that mounts actions, and every `Action` instance itself, depends on
this bundle only indirectly — through the `@SylamentActions` Twig namespace its
registration makes available to `Action::$template`
([`Action.php`](../../../packages/actions/src/Action.php)) and to the modal
partial. Most of what the bundle does is still convention rather than code: it
inherits the `@SylamentActions` Twig namespace by AbstractBundle/TwigBundle
convention, and `Action` and its modal render through support's/schemas'
`ViewComponent::render()` with nothing to wire. Its two `loadExtension()`
responsibilities have no Filament counterpart — `ActionsServiceProvider` binds no
container services: the host-side
[`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) component is
a **class**-based Twig component, so it must be registered and tagged
`twig.component`, and the
[`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php)
supplying the `renderAction()` template helper is registered and tagged
`twig.extension` (see their rows). Asset registration,
the `filament.actions` middleware group, the export/import routes and
migrations, the `Make*Exporter`/`Make*Importer` commands, translations, and the
`TestsActions` `Testable` mixin are not ported yet — all of them either serve
the unbuilt Exports/Imports subsystem or are Livewire-specific.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/ActionsServiceProvider.php`](../../../vendor/filament/actions/src/ActionsServiceProvider.php)
  — the provider whose `configurePackage()` / `packageRegistered()` /
  `packageBooted()` this bundle ports
- [`actions/routes/web.php`](../../../vendor/filament/actions/routes/web.php),
  [`actions/database/migrations/`](../../../vendor/filament/actions/database/migrations)
  — what `hasRoutes('web')` / `hasMigrations()` actually register, read to judge
  what the routes/migrations row is blocked on

## Surface

Every provider responsibility. _Blocked by_ names what has to land first.
_Detail_ carries a **detail** link to a [Details](#details) write-up, a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle registration + `@SylamentActions` view namespace](../../../packages/actions/src/SylamentActionsBundle.php) | [`->hasViews()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| [Twig component service registration](../../../packages/actions/src/SylamentActionsBundle.php) (`loadExtension()`) | — (new; Filament's Blade components need no service) | done | — | [diverges](#the-bundle-registers-a-twig-component-service-which-filament-has-no-counterpart-for) |
| [Twig extension service registration](../../../packages/actions/src/SylamentActionsBundle.php) (`loadExtension()`, `renderAction()`) | — (Sylament-only; mirrors schemas' [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | — | [diverges](#the-bundle-registers-a-renderaction-twig-extension-which-filament-has-no-counterpart-for) |
| [`@sylament/actions` asset workspace](../../../packages/actions/assets/package.json) | — (new; Filament ships a prebuilt `dist/index.js` and no workspace) | done | — | [diverges](#the-asset-workspace-is-js-only-and-its-importmap-block-names-only-stimulus) |
| [Page-wide dispatch controller](../../../packages/actions/assets/src/controllers/dispatch_controller.ts) | — (Sylament-only; Filament dispatches from an inline `wire:click` expression) | done | — | [diverges](#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it) |
| Asset registration (`Js::make('actions', dist/index.js)`) | [`packageBooted()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | todo | — | [detail](#asset-registration) |
| Exports/Imports provider wiring (`hasMigrations`, `hasRoutes('web')`, `filament.actions` middleware group, `hasCommands`) | [`configurePackage()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php), [`packageRegistered()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | blocked | Exports/Imports subsystem | [detail](#exportsimports-provider-wiring) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | todo | — | [detail](#translations) |
| `Testable::mixin(new TestsActions)` | [`packageBooted()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

The bundle-load + view-namespace row is covered indirectly:
[`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php),
[`ButtonRenderTest`](../../../packages/actions/tests/Integration/Components/ButtonRenderTest.php),
[`ActionModalRenderTest`](../../../packages/actions/tests/Integration/Components/ActionModalRenderTest.php),
[`ModalRenderTest`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php),
and the `Concerns/HasActions*` Integration tests all boot
[`tests/Fixtures/Kernel.php`](../../../packages/actions/tests/Fixtures/Kernel.php)
(which registers `SylamentActionsBundle`) and render `Action` through
`ViewComponent::render()`, proving the bundle loads and its
`@SylamentActions/partials/Action/Button.html.twig` /
`@SylamentActions/components/ActionModal/index.html.twig` templates resolve.
[`ModalsRenderTest`](../../../packages/actions/tests/Integration/Components/ModalsRenderTest.php)
covers the Twig-component registration row: `<twig:SylamentActions:Modals />`
only resolves if the service was registered and tagged.
[`ActionExtensionTest`](../../../packages/actions/tests/Integration/Twig/Extension/ActionExtensionTest.php)
covers the Twig-extension row the same way: its fixture host template's
`{{ renderAction('delete') }}` only renders if the service was registered and
tagged `twig.extension`. The unported
responsibilities have no coverage because they have no code.

## Details

### Asset registration

Filament's `packageBooted()` calls `FilamentAsset::register([...],
'filament/actions')` with a single `Js::make('actions', __DIR__ .
'/../dist/index.js')` (the actions Alpine bundle: modal open/close, confirmation
wiring). Sylament's translation is a `prependExtension()` that registers
`packages/actions/assets/dist` as `@sylament/actions` (mirroring
[`SylamentSupportBundle::prependAssetMapper()`](../../../packages/support/src/SylamentSupportBundle.php)
and [`SylamentNotificationsBundle::prependAssetMapper()`](../../../packages/notifications/src/SylamentNotificationsBundle.php)).

The deferral this section used to carry — "once the actions Stimulus controller
is ported" — is over: `packages/actions/assets/dist` now exists and holds the
built [dispatch controller](#the-dispatch-controller-is-page-wide-and-filament-has-no-counterpart-for-it).
The bundle still has **no** `prependExtension()` override, and that is now the
one thing standing between the built controller and a consuming app. The base
[`SylamentBundle`](../../../packages/support/src/SylamentBundle.php) supplies only
the `isAssetMapperAvailable()` guard — each bundle writes its own prepend, and
only `support` and `notifications` have — so nothing maps the directory today.

The failure mode is loud rather than silent, which is the one mercy here.
StimulusBundle resolves a `controllers.json` entry by reading the PHP package's
`assets/package.json` ([`UxPackageReader`](../../../vendor/symfony/stimulus-bundle/src/Ux/UxPackageReader.php)
turns `@sylament/actions` back into the `sylament/actions` install path), then
calls `assetMapper->getAssetFromSourcePath()` on `<packageDirectory>/` plus the
controller's `main` and **throws** `Could not find an asset mapper path that
points to the "…" controller` when the path sits outside every registered one
([`ControllersMapGenerator`](../../../vendor/symfony/stimulus-bundle/src/AssetMapper/ControllersMapGenerator.php)).
So an app that enables the controller without the prepend fails at
controllers-map generation, not at the first click.

**Next step:** add `prependExtension()` → `prependAssetMapper()` to
[`SylamentActionsBundle`](../../../packages/actions/src/SylamentActionsBundle.php),
a byte-for-byte sibling of notifications', and flip this row to **done** with the
Integration test that file's own [asset-registration
row](../notifications/notifications-bundle.md#asset-registration) already wants —
one shape written once covers `@sylament/support`, `@sylament/notifications` and
`@sylament/actions`.

### Exports/Imports provider wiring

Four `configurePackage()`/`packageRegistered()` calls exist solely to support
Filament's background export/import feature, which has no Sylament port at all
(the `actions` [package README](README.md) lists `Exporter`, `Importer`,
`ExportAction`, `ImportAction`, and the whole `Exports/`/`Imports/`/`Commands/`
subtrees as `todo`):

- `->hasMigrations(['create_imports_table', 'create_exports_table',
  'create_failed_import_rows_table'])` — Eloquent migrations for the
  [`Export`](../../../vendor/filament/actions/src/Exports/Models/Export.php) /
  [`Import`](../../../vendor/filament/actions/src/Imports/Models/Import.php) /
  `FailedImportRow` models. No Doctrine entity or migration equivalent exists.
- `->hasRoutes('web')` registers
  [`routes/web.php`](../../../vendor/filament/actions/routes/web.php): two
  `Route::get()`s (`DownloadExport`, `DownloadImportFailureCsv`) under a
  `filament.actions`-middleware, `filament`-prefixed group. No Symfony route
  equivalent exists.
- `packageRegistered()`'s `Router::middlewareGroup('filament.actions',
  ['web'])` exists only to gate the two routes above.
- `->hasCommands([MakeExporterCommand::class, MakeImporterCommand::class])` —
  stub-publishing `make:` commands for user `Exporter`/`Importer` classes. No
  `Commands/` subtree exists in the port (tracked as the `Commands` and
  `Commands / FileGenerators` `todo` rows in the
  [package README](README.md)).

**Next step:** none of this can be ported before the Exports/Imports subsystem
itself lands — the migrations describe tables the port has no models for, the
routes serve controllers the port doesn't have, and the commands generate
classes (`Exporter`/`Importer`) the port doesn't define. All four are **blocked**
on that subsystem, not skipped: background export/import is a wanted parity
feature, just not started.

### Translations

`->hasTranslations()` registers Filament's `resources/lang` namespace (labels
for the confirmation modal, notification titles, and generated import/export
messages). Unported. **Next step:** port when the translations subtree is
tackled — independent of the Exports/Imports blocker above, since the
confirmation-modal strings apply to the already-ported `Action`/`CanRequireConfirmation`.

## Divergences

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace. Sylament
registers nothing in the bundle file (its body is a bare `//`):
`AbstractBundle::getPath()` returns `packages/actions`, and TwigBundle
auto-maps `packages/actions/templates` → `@SylamentActions`. The wiring is
therefore invisible in the bundle — intentional, and the reason
[`Action`](../../../packages/actions/src/Action.php) can default
`$defaultTemplate` to `self::BUTTON_TEMPLATE`
(`'@SylamentActions/partials/Action/Button.html.twig'`) with no explicit
twig path config. Same divergence as
[forms-bundle.md](../forms/forms-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call)
and
[schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).

### The bundle registers a Twig component service, which Filament has no counterpart for

Filament's Blade components need no service registration at all — Blade resolves
`<x-filament-actions::modals />` from the view namespace. Sylament's
[`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) is a class,
so it has to be a container service tagged `twig.component`. Two choices in
[`loadExtension()`](../../../packages/actions/src/SylamentActionsBundle.php) are
worth knowing:

- `->autoconfigure()` lets the `#[AsTwigComponent]` attribute supply the tag
  (ux-twig-component registers it for autoconfiguration), keeping the attribute
  the single source of truth for the component's name and template instead of
  duplicating both as tag attributes here.
- The component stack is injected by **service id**
  (`ux.twig_component.component_stack`), not by type: ux-twig-component registers
  no class alias for it, so autowiring would fail.

Registration is unconditional, unlike
[`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php)'s
optional-dependency guard, because `symfony/ux-twig-component` is a hard `require`
of this package — see
[the dependency rule](#every-package-declares-what-its-own-source-needs-transitives-notwithstanding).

### The bundle registers a `renderAction()` Twig extension, which Filament has no counterpart for

Filament host templates render an action by echoing the object itself —
`Action` extends a `ViewComponent` that implements `Htmlable`
([`ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)),
so Blade renders `{{ $action }}` via `toHtml()` and the provider wires nothing.
Twig has no equivalent implicit-render path, so the bundle registers
[`ActionExtension`](../../../packages/actions/src/Twig/Extension/ActionExtension.php)
(`sylament_actions.action_extension`, tagged `twig.extension`) supplying
`renderAction(name)` — the actions twin of schemas'
[`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php),
with the wiring mirrored deliberately: support's
`sylament_support.component_extension` is constructor-injected by service id, and
the function registers with `needs_environment` + `needs_context`. The helper
resolves the name through the **surrounding live component's** `getAction()` (so
the rendered action is bound to its host), renders nothing when the name does not
resolve or the action is hidden, and throws a `RuntimeException` when the
enclosing `this` is not an
[`InteractsWithActions`](../../../packages/actions/src/Contracts/InteractsWithActions.php)
host. An optional second parameter binds per-render arguments —
`renderAction('delete', { id: 5 })` — through `HasMountableArguments`'s
`__invoke()`, which Twig syntax cannot call directly: the bound instance is a
clone, so the host-cached action stays argument-free, and the serialized
`arguments` mount param reaches the trigger markup. The order is resolve →
bind → hidden-check, so a visibility closure can read the bound arguments.

### The asset workspace is JS-only, and its importmap block names only Stimulus

`packages/actions/assets` joins the pnpm workspace through
[`pnpm-workspace.yaml`](../../../pnpm-workspace.yaml)'s `packages/*/assets` glob
the moment it gains a `package.json`; its `tsconfig.json` and
`scripts/build-js.mjs` are byte-copies of notifications', `BUNDLED_ENTRIES` empty,
so every entry ships per-file and unbundled with its import specifiers verbatim.
`@symfony/ux-live-component` is declared dev **and** peer at `^3.2.0` — the npm
version the installed PHP package v3.3.0 ships, the two halves versioning
independently — and stays a bare specifier in the built file, resolved by the
consuming app's importmap.

No `symfony.importmap` entry is declared for it, and that is a decision rather
than an omission — the same one notifications' workspace
[records](../notifications/notifications-bundle.md#asset-registration-is-an-assetmapper-prepend-and-the-css-rides-supports-build).
The block names what a consumer's importmap must hold and might not otherwise
get; here this package's own composer manifest requires
`symfony/ux-live-component`, whose recipe installs the entry as a **path** to the
vendored `live_controller.js`, and a version constraint beside it would name the
CDN copy — a second artifact registering a second `live` controller. The blast
radius if the path entry is missing anyway is the whole controller, not one arm:
`@symfony/ux-live-component` is a **top-level** import in the built
`dispatch_controller.js`, so an importmap without it 404s the module before
`afterLoad()` ever runs, and every dispatch on the page dies with it.

Its stylesheet is *not* built here either. Support's
`assets/src/styles/index.css` imports this package's
[`src/styles/index.css`](../../../packages/actions/assets/src/styles/index.css)
directly, so the `sy-ac-*` rules compile into support's single built stylesheet;
`build` only ever emits the JS, and actions ships no CSS dist of its own.

### The dispatch controller is page-wide, and Filament has no counterpart for it

Filament needs no such controller: its trigger Blade emits
`wire:click="$dispatch(…)"`, an inline JavaScript expression Livewire evaluates
against whatever component encloses it. The port has no arbitrary-JS seam and
[does not want one](action.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding),
so the templates stamp payload attributes and something else does the delivery.
[`dispatch_controller.ts`](../../../packages/actions/assets/src/controllers/dispatch_controller.ts)
is that something, and it is deliberately *not* a per-element controller.

**It never binds to an element.** Stimulus calls `static afterLoad()` once, at
**registration** time, with the identifier and the application rather than a
host element (verified against the installed 3.2.2
`Controller.afterLoad(_identifier, _application)`), so a controller class can run
setup for a page that contains none of its elements — and this one contains none
by design, since no template emits `data-controller="sylament--actions--dispatch"`.
`fetch: eager` in the `symfony.controllers` block is what makes that fire at all:
a lazy controller is registered only when a matching element appears, which for
an element-less controller is never. A module-level `listening` flag guards the
one `document.addEventListener` against a double registration.

**It listens in the capture phase**, and that is the load-bearing choice. A
delegated listener on `document` in the bubble phase is at the mercy of every
ancestor between the trigger and the root: support's section header already calls
`event.stopPropagation()` in
[`toggleFromButton`](../../../packages/support/assets/src/controllers/section_controller.ts),
and any future `:stop` action option would do the same silently. Capture runs
top-down before all of them. It also fixes the ordering `dispatch()->close()`
depends on — the toast root's `handleActionClick` is a plain bubble-phase
Stimulus action, so the emit is guaranteed to happen before the toast takes
itself out of the DOM.

**The arms, in order.** From `event.target` the handler walks
`closest('[data-sylament-action-event]')`; a trigger with no event name is not a
dispatch and is dropped. Then, inside one `try`:

1. `data-sylament-action-dispatch-self` present → resolve the trigger's *closest*
   `[data-controller~="live"]` ancestor and `emitSelf(name, data)` on it. This is
   the only arm that reads the DOM relative to the trigger, and the one that will
   give `dispatchSelf()` real self semantics — no template
   [emits that attribute yet](action.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding),
   so as of this commit the read is inert, which is exactly what reading an
   always-absent attribute costs.
2. No self marker, or no live ancestor to be self of → the page's **first**
   `[data-controller~="live"]` element, `emit(name, data, to)`, with
   `data-sylament-action-dispatch-to` passed as `emit()`'s third argument and
   `null` when absent or empty. Any host works as the emitter: live's `emit()`
   broadcasts to every component on the page, and `dispatchTo`'s name filter
   narrows globally, which is what lets an action rendered outside every live
   component dispatch into one at all.
3. Anything that rejects or resolves to nothing — `getComponent()` on an element
   the live application has not registered yet (a lazy or boot race), or a page
   with no live component at all — is **silent**. That matches a dispatch nobody
   listens to, and it is why the `catch` is empty but for its why-swallow comment.

**Close is deliberately not handled here.** `data-sylament-action-close` is a
neutral marker meaning *this action closes its context*, and only the context
knows what closing means — a toast removes itself, a modal dismisses. So the
close arm stays with whoever owns the click context (the toast root's controller,
support's modal controller) and this controller reads the attribute nowhere. The
split is the same one the [payload attributes](action.md#a-dispatch-action-renders-payload-attributes-not-a-click-binding)
were designed around: templates stamp payload, context owners deliver behaviour,
and dispatch is the one behaviour that has no context.

### Every package declares what its own source needs, transitives notwithstanding

`symfony/ux-twig-component` is required by **both** `support` and `actions`, for
two independent reasons, and neither is redundant:

- `support` ships the six anonymous Twig components, and every one of their
  templates opens with `{% props %}` — a token parser that **only** ux-twig-component
  provides. Without it those templates do not merely fail to render, they fail to
  parse. It was a `suggest` until this change; that was wrong, since a package
  whose shipped artifacts cannot be compiled without a dependency has a hard one.
- `actions` imports the namespace as real PHP symbols —
  [`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) type-hints
  `AsTwigComponent` and `ComponentStack` — so it declares it directly rather than
  leaning on `support` to keep providing it.

The rule the monorepo follows: **declare what your own `src/` and `templates/`
need.** Inheriting a dependency through a sibling is how the split repos silently
break — this audit found `actions` declaring neither `symfony/ux-live-component`,
though [`HasActions`](../../../packages/actions/src/Concerns/HasActions.php) does
`use ComponentToolsTrait` (a hard compile-time requirement) plus `#[LiveProp]` /
`#[LiveAction]`, nor `symfony/string`, which
[`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) imports for
`u()`. Until both were declared the split `sylament/actions` repo could not load
`HasActions` at all; it worked in the monorepo only because `sylament/schemas`
happens to require `symfony/ux-live-component` at the root.

Those two stay on `actions` and do **not** move up to `support`: support's `src/`
references `Symfony\UX\LiveComponent` nowhere, none of its templates call
`live_action()`, and nothing in it uses `symfony/string`. Pushing them down to the
base package would have it declare dependencies it does not use — the same defect
in reverse.

The same audit swept all nine packages. It added `symfony/ux-twig-component` and
`symfony/string` to `forms` (its `FieldWrapper` template opens with `{% props %}`
and [`Field`](../../../packages/forms/src/Components/Field.php) imports `u()`), and
found the five placeholder bundles — `infolists`, `notifications`, `panels`,
`tables`, `widgets` — import nothing but `SylamentBundle`, so `sylament/support`
alone is already complete for them. The rule adds nothing to a package that uses
nothing. `twig/twig` and the ambient `symfony/dependency-injection` /
`symfony/http-kernel` omissions are deliberate and recorded in
[CLAUDE.md](../../../CLAUDE.md).

The schemas↔actions integration epic adds the rule's first genuinely mutual
edge: [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php)
type-hints `Schemas\Components\Component` and `Schemas\Schema` directly, so
`actions` now requires `sylament/schemas`. `schemas` requires `sylament/actions`
back in the same commit, ahead of the schemas-side code (the component-level
`HasActions` registration trait, `CanModifyActions`, the `HasComponents`
child-schema unions, and everything the rest of the epic built on top) that
goes on to import `Action`/`ActionGroup` throughout the schemas↔actions
integration epic — see
[schemas-bundle.md](../schemas/schemas-bundle.md#sylamentactions-becomes-a-hard-composer-requirement).
Filament already carries this cycle: `filament/schemas`'s composer.json requires
`filament/actions` directly, and `filament/actions` reaches `filament/schemas`
back only transitively, through `filament/forms` (which requires both). Sylament
states the edge the two packages already share in Filament's graph directly,
rather than through three packages — `forms`, `infolists`, `notifications` — that
don't provide it here yet.

The tables↔actions seam is the counter-case, and it is where the rule's *own*
boundary sits: `HasActions` names tables'
[`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php)
in `src/`, yet `actions` requires `sylament/tables` nowhere. The reference is
never unconditional — an `instanceof` guard stands in front of every use, and the
branch behind it is simply dead in an install without the package — so it earns a
composer `suggest` and the guard rather than a `require`, the same shape support's
`isAssetMapperAvailable()` has carried since the beginning. Reasoning and the
test that keeps the soft reference honest:
[has-actions.md](has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference).

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers `Testable::mixin(new TestsActions)` — a Livewire
`Testable` mixin (`mountAction()`, `callAction()`, `assertActionExists()`, …)
whose every method calls into `$this->instance()` (a Livewire component) via
`$this->call()`/`$this->set()`. Sylament tests are Pest/`KernelTestCase`, not
Livewire `Testable`; the Integration suite renders `Action` directly through
`ViewComponent::render(Environment $twig)`
([`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php))
rather than driving a mounted host component's action lifecycle through
assertion helpers. This is a deliberate non-goal — **skipped** — for the same
reason forms' `TestsForms`/`TestsFormComponentActions` mixins are skipped
([forms-bundle.md](../forms/forms-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported)).
`TestsActions` and its `TestAction` helper are tracked as their own `todo` rows
in the [package README](README.md) supporting surface.

_The `ActionsServiceProvider` → `SylamentActionsBundle` swap (and the underlying
Spatie `PackageServiceProvider` → Symfony `AbstractBundle` move) is a systematic
Symfony-idiom translation, like those the [rename table](../renames.md) collects
under "Not renames" — Laravel package tooling with no per-name Sylament
equivalent to enumerate — not a per-item rename or divergence. The
`InteractsWithActions`/`HasActions` names this file's neighbors use for the
host contract and trait are the recorded
[Has\*/InteractsWith\* swap](../renames.md#interface-and-trait-names-are-swapped),
referenced here rather than re-derived._
