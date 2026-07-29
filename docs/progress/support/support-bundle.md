# Support bundle (support)

`Sylament\Support\SylamentSupportBundle` (extending the shared
`Sylament\Support\SylamentBundle`) — the Symfony `AbstractBundle` that ports
Filament's `SupportServiceProvider`. It wires the package's services through the
PHP DI configurator, registers the Twig extensions, boots the static-instance
registry, and prepends the bundle's built assets into AssetMapper. Filament's
provider is a Spatie `PackageServiceProvider` whose register/boot surface is
mostly Laravel/Livewire/Blade bootstrapping — only the parts with a Symfony
analog are ported; the rest are divergences below or tracked as their own rows
in the [package README](README.md).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php)
  — `configurePackage()`, `packageRegistered()`, `packageBooted()`

## Surface

Every point of the bundle wiring. _Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details) and/or
**diverges** → a permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Service wiring](../../../packages/support/src/SylamentSupportBundle.php) — `loadExtension()` PHP DSL: `ComponentManager`, `IconManager`, `Invoker`, `StaticInstanceRegistry`, five Twig extensions | [`packageRegistered()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | — |
| [Static-instance boot](../../../packages/support/src/SylamentSupportBundle.php) — `build()` adds `StaticInstancePass`; `boot()` runs `StaticInstanceRegistry::bootStaticInstances()` | [`packageRegistered()`](../../../vendor/filament/support/src/SupportServiceProvider.php) (`scoped`/`singleton` + `app->booted`) | done | — | [diverges](#static-instance-boot-replaces-laravels-container-scoping) |
| [Asset registration](../../../packages/support/src/SylamentSupportBundle.php) — `prependExtension()` → `prependAssetMapper()` guarded by [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) | [`FilamentAsset::register([Js::make('support', …)])`](../../../vendor/filament/support/src/SupportServiceProvider.php) | in progress | — | [detail](#asset-registration) |
| Livewire request / component wiring | [`packageRegistered()`/`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | blocked | live-component-state work | [detail](#livewire-request-and-component-wiring-is-not-ported) |
| Blade directives & string macros | [`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | skipped | — | [diverges](#blade-directives-and-string-macros-are-not-ported) |
| PackageServiceProvider bootstrapping — config/views/translations publishing, commands, `AboutCommand`, `optimizes` | [`configurePackage()`/`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | skipped | — | [diverges](#packageserviceprovider-bootstrapping-is-replaced-by-abstractbundle-mechanics) |

## Details

### Asset registration

`prependAssetMapper()` registers `packages/support/assets/dist` into
`framework.asset_mapper.paths` under the alias `@sylament/support`, but only when
[`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php)
confirms `AssetMapperInterface` exists and FrameworkBundle ships
`Resources/config/asset_mapper.php` — the guard that keeps `symfony/asset-mapper`
an optional dependency for production consumers. This is the faithful Symfony
analog of Filament registering `Js::make('support', …)` with `FilamentAsset`.

What is missing: **no test.** Nothing asserts the path/alias is prepended when
AssetMapper is present, and nothing exercises the negative branch of
`isAssetMapperAvailable()` (the short-circuit that returns without prepending).
The bundle's other wiring is covered by
[`SylamentSupportBundleTest`](../../../packages/support/tests/Integration/SylamentSupportBundleTest.php),
but that suite never inspects the prepended asset config. Next step: an
Integration test asserting the compiled `framework.asset_mapper.paths` contains
`@sylament/support`, plus a Unit test for the guard's false branch — then this
row flips to **done**.

### Livewire request and component wiring is not ported

The provider's `DataStore` override, `PartialsComponentHook`, the
`originalRequest` scoped binding, and the `ScopedComponentManager` clone are all
Livewire-runtime plumbing — request-scoped state, per-request component hooks, and
config isolation. There is no Livewire here (the idiom translation is UX
LiveComponent), and Filament's specific seams won't be ported verbatim; but the
equivalent live-component request wiring is wanted, so the row is **blocked** on
the live-component-state work, where Sylament grows its own data store, component
hooks, and per-request scope.

_See also: the [Livewire → UX LiveComponent](../renames.md#idiom) idiom
translation._

## Divergences

### Static-instance boot replaces Laravel's container scoping

Filament registers its managers as Laravel `scoped()`/`singleton()` services and
re-resolves the scoped `ComponentManager` on `app->booted` (and on Octane's
`RequestReceived`). Sylament has no request-scoped container, so the managers are
plain services made statically reachable through the
[static-instance pattern](../../../packages/support/src/DependencyInjection/StaticInstanceRegistry.php):
`build()` adds `StaticInstancePass` (auto-tags every `StaticInstanceInterface`
definition) and `boot()` calls `StaticInstanceRegistry::bootStaticInstances()`,
which sets each tagged service as its class's static instance.

`StaticInstancePass` decides by `is_a($class, StaticInstanceInterface::class, true)`,
which autoloads the class — and it swallows the `Throwable` that raises. A bundle
may register a service whose class extends an optional dependency that is not
installed (LiveComponent's `form.live_collection` extends Symfony Form's
`AbstractType`, so autoloading it fatals when `symfony/form` is absent), and a
class that cannot even load is never a `StaticInstance`, so skipping it is safe
and keeps the pass from breaking unrelated bundles.

The `ScopedComponentManager` clone and the `RequestReceived` re-resolution have no
analog and are dropped; `ScopedComponentManager` is tracked as its own **todo**
row in the [package README](README.md).

### Blade directives and string macros are not ported

`packageBooted()` registers Blade directives (`@capture`, `@endcapture`,
`@captureSlots`, `@filamentScripts`, `@filamentStyles`, `@trim`) and `Str` /
`Stringable` macros (`sanitizeHtml`, `sanitizeUrl`, `sanitizeCssColor`,
`ucwords`). There is no Blade — templating is Twig — and macro-based extension is
a rejected pattern here (see
[Macroable is not ported](component.md#macroable-is-not-ported)). The Blade
directives have no Twig equivalent yet; the sanitizer logic, if wanted, would
land as helpers or Twig filters rather than string macros. Two
`ComponentAttributeBag` macros from the same method *are* ported and so are not
part of this divergence: `macro('grid')` and `macro('gridColumn')` land as the
`grid()`/`gridColumn()` Twig functions in
[`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php).
The third, `macro('color')`, is **not** ported — it depends on `ColorManager` /
`FilamentColor`, which are tracked as their own **todo** rows in the
[package README](README.md) — so it awaits those, not this divergence.

### The ported `grid()` / `gridColumn()` also drop blank breakpoint *keys*

Filament's two macros filter their breakpoint maps by value only
(`array_filter($columns)`). The
[`GridExtension`](../../../packages/support/src/Twig/Extension/GridExtension.php)
port adds a second pass over the **keys**
(`array_filter(…, filled(...), ARRAY_FILTER_USE_KEY)`), because every non-`default`
key is interpolated straight into a class name, so a blank key would emit a
garbage `<whitespace>:sy-grid-*` class. Filament interpolates the same way and has
no guard — the port hardens the input rather than reproducing that.

### PackageServiceProvider bootstrapping is replaced by AbstractBundle mechanics

Filament's provider extends Spatie's `PackageServiceProvider`:
`configurePackage()` declares commands, a config file, translations and views,
and `packageBooted()` publishes config, wires `AboutCommand` info, and calls
`optimizes()`. None of these Laravel package-tooling affordances carries over —
Symfony handles the equivalents differently and elsewhere: asset registration is
the AssetMapper prepend above (not asset publishing), configuration is Symfony
bundle config, and console commands are auto-registered by tag when they exist.
The command classes, translations, and config surface are tracked as their own
**todo** rows in the [package README](README.md), so they are not enumerated
here.

_See also: the global [ServiceProvider → Bundle translation](../renames.md) —
the base-class swap is a systematic idiom translation, not a per-item divergence._
