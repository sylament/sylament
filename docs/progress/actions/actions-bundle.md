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
`ViewComponent::render()` with nothing to wire. Its one `loadExtension()`
responsibility has no Filament counterpart — `ActionsServiceProvider` binds no
container services — and exists because the host-side
[`Modals`](../../../packages/actions/src/Twig/Components/Modals.php) component is
a **class**-based Twig component, so it must be registered and tagged
`twig.component` (see its own row). Asset registration,
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
`@SylamentActions/components/ConfirmModal/index.html.twig` templates resolve.
[`ModalsRenderTest`](../../../packages/actions/tests/Integration/Components/ModalsRenderTest.php)
covers the Twig-component registration row: `<twig:SylamentActions:Modals />`
only resolves if the service was registered and tagged. The unported
responsibilities have no coverage because they have no code.

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
[`Action`](../../../packages/actions/src/Action.php) can default
`$defaultTemplate` to `self::BUTTON_TEMPLATE`
(`'@SylamentActions/components/button-action.html.twig'`) with no explicit
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

### Every package declares what its own source needs, transitives notwithstanding

`symfony/ux-twig-component` is required by **both** `support` and `actions`, for
two independent reasons, and neither is redundant:

- `support` ships the five anonymous Twig components, and every one of their
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
