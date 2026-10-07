# Support bundle (support)

`Sylament\Support\SylamentSupportBundle` (extending the shared `Sylament\Support\SylamentBundle`) — the Symfony `AbstractBundle` that ports Filament's `SupportServiceProvider`.
It wires the package's services through the PHP DI configurator, registers the Twig extensions, boots the static-instance registry, and prepends the bundle's built assets into AssetMapper.
Filament's provider is a Spatie `PackageServiceProvider` whose register/boot surface is mostly Laravel/Livewire/Blade bootstrapping — only the parts with a Symfony analog are ported; the rest are divergences below or tracked as their own rows in the [package README](README.md).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) — `configurePackage()`, `packageRegistered()`, `packageBooted()`

## Surface

Every point of the bundle wiring.
_Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details) and/or **diverges** → a permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Service wiring](../../../packages/support/src/SylamentSupportBundle.php) — `loadExtension()` PHP DSL: `ColorManager`, `ComponentManager`, `ComponentResolver`, `FilesystemRegistry`, `IconManager`, `Invoker`, `StaticInstanceRegistry`, six Twig extensions | [`packageRegistered()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | — |
| [Semantic config](../../../packages/support/src/SylamentSupportBundle.php) — `configure()` declares the `sylament` extension and its four nodes: `colors`, `icons`, `default_filesystem_disk`, `temporary_file_url_expiry_minutes` | `config/filament.php` for the last two (`default_filesystem_disk`, `temporary_file_url_expiry_minutes`); no Filament equivalent for the first two (Laravel publishes a config file instead) | done | — | [diverges](icon-manager.md#alias-overrides-are-declarable-from-bundle-configuration) · [filesystem-registry.md](filesystem-registry.md) |
| [Static-instance boot](../../../packages/support/src/SylamentSupportBundle.php) — `build()` adds `StaticInstancePass`; `boot()` runs `StaticInstanceRegistry::bootStaticInstances()` | [`packageRegistered()`](../../../vendor/filament/support/src/SupportServiceProvider.php) (`scoped`/`singleton` + `app->booted`) | done | — | [diverges](#static-instance-boot-replaces-laravels-container-scoping) |
| [Asset registration](../../../packages/support/src/SylamentSupportBundle.php) — `prependExtension()` → `prependAssetMapper()` guarded by [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) | [`FilamentAsset::register([Js::make('support', …)])`](../../../vendor/filament/support/src/SupportServiceProvider.php) | in progress | — | [detail](#asset-registration) |
| Livewire request / component wiring | [`packageRegistered()`/`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | blocked | live-component-state work | [detail](#livewire-request-and-component-wiring-is-not-ported) |
| Blade directives & string macros | [`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | skipped | — | [diverges](#blade-directives-and-string-macros-are-not-ported) |
| PackageServiceProvider bootstrapping — config/views/translations publishing, commands, `AboutCommand`, `optimizes` | [`configurePackage()`/`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | skipped | — | [diverges](#packageserviceprovider-bootstrapping-is-replaced-by-abstractbundle-mechanics) |

## Details

### Asset registration

`prependAssetMapper()` registers `packages/support/assets/dist` into `framework.asset_mapper.paths` under the alias `@sylament/support`, but only when [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) confirms `AssetMapperInterface` exists and FrameworkBundle ships `Resources/config/asset_mapper.php` — the guard that keeps `symfony/asset-mapper` an optional dependency for production consumers.
This is the faithful Symfony analog of Filament registering `Js::make('support', …)` with `FilamentAsset`.

What lands in that directory is [`assets/scripts/build-js.mjs`](../../../packages/support/assets/scripts/build-js.mjs)'s business: every `src/**/*.ts` builds per-file and unbundled, its import specifiers shipped verbatim for the consuming app's importmap to resolve, except the entries in that script's `BUNDLED_ENTRIES` table — the `dropdown` controller, bundling `@floating-ui/dom`, and the `sortable` controller, bundling `sortablejs` ([sortable.md](sortable.md#sortablejs-is-bundled-twice-pinned-to-one-version)) — which inline their library behind a licence banner composed from the installed package and keep `@hotwired/stimulus` external.
So the prepended directory needs no importmap entry beyond Stimulus itself.

What is missing: **no test.**
Nothing asserts the path/alias is prepended when AssetMapper is present, and nothing exercises the negative branch of `isAssetMapperAvailable()` (the short-circuit that returns without prepending).
The bundle's other wiring is covered by [`SylamentSupportBundleTest`](../../../packages/support/tests/Integration/SylamentSupportBundleTest.php), but that suite never inspects the prepended asset config.

**Next step:** an Integration test asserting `@sylament/support` maps the built directory, plus a Unit test for the guard's false branch — then this row flips to **done**.
The positive half is already written, for the bundle that needed it first: [`SylamentActionsBundleTest`](../../../packages/actions/tests/Integration/SylamentActionsBundleTest.php) asks the real `AssetMapperInterface` for a built asset by source path and requires the logical path back.
Copy that shape rather than asserting the compiled `framework.asset_mapper.paths` array directly: the AssetMapper seam is the call StimulusBundle's controllers-map generator actually makes, so a prepend that is present but misaliased fails it where a config-array assertion passes.
It was [written once there to be copied](../actions/actions-bundle.md#asset-registration-is-an-assetmapper-prepend), by this row and by [notifications'](../notifications/notifications-bundle.md#asset-registration).
The guard's false branch is **this** package's to pin for all three, since [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php) owns it.

### Livewire request and component wiring is not ported

The provider's `DataStore` override, `PartialsComponentHook`, the `originalRequest` scoped binding, and the `ScopedComponentManager` clone are all Livewire-runtime plumbing — request-scoped state, per-request component hooks, and config isolation.
There is no Livewire here (the idiom translation is UX LiveComponent), and Filament's specific seams won't be ported verbatim; but the equivalent live-component request wiring is wanted, so the row is **blocked** on the live-component-state work, where Sylament grows its own data store, component hooks, and per-request scope.

_See also: the [Livewire → UX LiveComponent](../renames.md#idiom) idiom translation._

## Divergences

### Static-instance boot replaces Laravel's container scoping

Filament registers its managers as Laravel `scoped()`/`singleton()` services and re-resolves the scoped `ComponentManager` on `app->booted` (and on Octane's `RequestReceived`).
Sylament has no request-scoped container, so the managers are plain services made statically reachable through the [static-instance pattern](../../../packages/support/src/DependencyInjection/StaticInstanceRegistry.php): `build()` adds `StaticInstancePass` (auto-tags every `StaticInstance` definition) and `boot()` calls `StaticInstanceRegistry::bootStaticInstances()`, which sets each tagged service as its class's static instance.

`StaticInstancePass` decides by `is_a($class, StaticInstance::class, true)`, which autoloads the class — and it swallows the `Throwable` that raises.
A bundle may register a service whose class extends an optional dependency that is not installed (LiveComponent's `form.live_collection` extends Symfony Form's `AbstractType`, so autoloading it fatals when `symfony/form` is absent), and a class that cannot even load is never a `StaticInstance`, so skipping it is safe and keeps the pass from breaking unrelated bundles.

The `ScopedComponentManager` clone and the `RequestReceived` re-resolution have no analog and are dropped; `ScopedComponentManager` is tracked as its own **todo** row in the [package README](README.md).

### Blade directives and string macros are not ported

`packageBooted()` registers Blade directives (`@capture`, `@endcapture`, `@captureSlots`, `@filamentScripts`, `@filamentStyles`, `@trim`) and `Str` / `Stringable` macros (`sanitizeHtml`, `sanitizeUrl`, `sanitizeCssColor`, `ucwords`).
There is no Blade — templating is Twig — and macro-based extension is a rejected pattern here (see [Macroable is not ported](component.md#macroable-is-not-ported)).
The Blade directives have no Twig equivalent yet; the sanitizer logic, if wanted, would land as helpers or Twig filters rather than string macros.
Two `ComponentAttributeBag` macros from the same method *are* ported and so are not part of this divergence: `macro('grid')` and `macro('gridColumn')` land as the `grid()`/`gridColumn()` Twig functions in [`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php).
The third, `macro('color')`, is ported the same way — as `colorAttributes()` on [`ColorExtension`](../../../packages/support/src/Twig/Extension/ColorExtension.php), backed by [`ColorManager`](color-manager.md) — so it is not part of this divergence either.
Of the four `Str` macros, `sanitizeCssColor` came with it, as a public method on the manager rather than a function in `helpers.php`, both of its callers being the manager's own emitters ([why](color-manager.md#the-css-colour-sanitizer-is-a-method-on-the-manager-and-drops-rather-than-nulls)); `sanitizeUrl` came the same way, as the pure [`sanitize_url()`](helpers.md) helper its first consumer — forms' `FileUpload` — imports with `use function`, since it needs no service at all.
`sanitizeHtml` and `ucwords` remain unported.

### The ported `grid()` / `gridColumn()` also drop blank breakpoint *keys*

Filament's two macros filter their breakpoint maps by value only (`array_filter($columns)`).
The [`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php) port adds a second pass over the **keys** (`array_filter(…, filled(...), ARRAY_FILTER_USE_KEY)`), because every non-`default` key is interpolated straight into a class name, so a blank key would emit a garbage `<whitespace>:sy-grid-*` class.
Filament interpolates the same way and has no guard — the port hardens the input rather than reproducing that.

### PackageServiceProvider bootstrapping is replaced by AbstractBundle mechanics

Filament's provider extends Spatie's `PackageServiceProvider`: `configurePackage()` declares commands, a config file, translations and views, and `packageBooted()` publishes config, wires `AboutCommand` info, and calls `optimizes()`.
None of these Laravel package-tooling affordances carries over — Symfony handles the equivalents differently and elsewhere: asset registration is the AssetMapper prepend above (not asset publishing), configuration is Symfony bundle config, and console commands are auto-registered by tag when they exist.
The command classes, translations, and config surface are tracked as their own **todo** rows in the [package README](README.md), so they are not enumerated here.

_See also: the global [ServiceProvider → Bundle translation](../renames.md) — the base-class swap is a systematic idiom translation, not a per-item divergence._
