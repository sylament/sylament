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
partial. Today the class is a bare `extends SylamentBundle` stub with an empty
body: it registers the bundle and inherits the `@SylamentActions` Twig namespace
by AbstractBundle/TwigBundle convention, which is all the shipped `Action`
surface needs — `Action` and its modal render through support's/schemas'
`ViewComponent::render()`, and `ActionsServiceProvider` binds no container
services, so there is nothing to wire in a `loadExtension()`. Asset registration,
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
| Asset registration (`Js::make('actions', dist/index.js)`) | [`packageBooted()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | todo | — | [detail](#asset-registration) |
| Exports/Imports provider wiring (`hasMigrations`, `hasRoutes('web')`, `filament.actions` middleware group, `hasCommands`) | [`configurePackage()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php), [`packageRegistered()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | blocked | Exports/Imports subsystem | [detail](#exportsimports-provider-wiring) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | todo | — | [detail](#translations) |
| `Testable::mixin(new TestsActions)` | [`packageBooted()`](../../../vendor/filament/actions/src/ActionsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

The bundle-load + view-namespace row is covered indirectly:
[`ActionRenderTest`](../../../packages/actions/tests/Integration/ActionRenderTest.php),
[`ButtonRenderTest`](../../../packages/actions/tests/Integration/Components/ButtonRenderTest.php),
[`ConfirmModalRenderTest`](../../../packages/actions/tests/Integration/Components/ConfirmModalRenderTest.php),
[`ModalRenderTest`](../../../packages/actions/tests/Integration/Components/ModalRenderTest.php),
and the `Concerns/HasActions*` Integration tests all boot
[`tests/Fixtures/Kernel.php`](../../../packages/actions/tests/Fixtures/Kernel.php)
(which registers `SylamentActionsBundle`) and render `Action` through
`ViewComponent::render()`, proving the bundle loads and its
`@SylamentActions/components/button-action.html.twig` /
`@SylamentActions/components/confirm-modal.html.twig` templates resolve. The
unported responsibilities have no coverage because they have no code.

## Details

### Asset registration

Filament's `packageBooted()` calls `FilamentAsset::register([...],
'filament/actions')` with a single `Js::make('actions', __DIR__ .
'/../dist/index.js')` (the actions Alpine bundle: modal open/close, confirmation
wiring). Sylament's translation is a `prependExtension()` that registers
`packages/actions/assets/dist` as `@sylament/actions` (mirroring
[`SylamentSupportBundle::prependAssetMapper()`](../../../packages/support/src/SylamentSupportBundle.php)).
Nothing is wired: the bundle has no `prependExtension()` override and
`packages/actions/assets/dist` does not exist (only
[`assets/src/styles/index.css`](../../../packages/actions/assets/src/styles/index.css)).
**Next step:** add the `prependExtension()` registration once the actions
Stimulus controller (modal open/close, confirmation) is ported — deferred until
that JS lands.

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
[`Action`](../../../packages/actions/src/Action.php) can set `$template =
'@SylamentActions/components/button-action.html.twig'` with no explicit twig
path config. Same divergence as
[forms-bundle.md](../forms/forms-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call)
and
[schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).

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
