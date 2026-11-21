---
description: The `SylamentSupportBundle` service wiring, config, static-instance boot and asset registration, and the package's stylesheet entry and core controller.
ports:
  - vendor/filament/support/src/SupportServiceProvider.php
  - vendor/filament/support/resources/lang/en/
  - vendor/filament/support/resources/css/index.css
  - vendor/filament/support/resources/css/base.css
owns:
  - packages/support/assets/src/controllers/core_controller.ts
  - packages/support/assets/src/styles/base.css
  - packages/support/assets/src/styles/components.css
  - packages/support/assets/src/styles/fonts.css
  - packages/support/assets/src/styles/index.css
  - packages/support/assets/src/utils/cloak.ts
  - packages/support/src/DependencyInjection/
  - packages/support/src/SylamentBundle.php
  - packages/support/src/SylamentSupportBundle.php
---
# Support bundle (support)

`Sylament\Support\SylamentSupportBundle`, extending the shared `Sylament\Support\SylamentBundle`, is the Symfony `AbstractBundle` that ports Filament's `SupportServiceProvider`.
It wires the package's services through the PHP DI configurator, registers the Twig extensions, boots the static-instance registry, and prepends the bundle's built assets into AssetMapper.
Filament's provider is a Spatie `PackageServiceProvider` whose register and boot surface is mostly Laravel, Livewire and Blade bootstrapping; only the parts with a Symfony analog are ported.
The file also tracks the package's asset entry points: the stylesheet every package's rules compile into, and the eager `core` controller that loads it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Service wiring: `loadExtension()`](../../../packages/support/src/SylamentSupportBundle.php) | [`packageRegistered()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [semantics](#the-colour-policies-are-registered-public) |
| [Semantic config: `configure()`](../../../packages/support/src/SylamentSupportBundle.php) | `config/filament.php` for `default_filesystem_disk` and `temporary_file_url_expiry_minutes` | done | — | [diverges](icon-manager.md#alias-overrides-are-declarable-from-bundle-configuration) · [filesystem-registry.md](filesystem-registry.md) |
| [Static-instance boot](../../../packages/support/src/SylamentSupportBundle.php) | [`packageRegistered()`](../../../vendor/filament/support/src/SupportServiceProvider.php) (`scoped`/`singleton` + `app->booted`) | done | — | [diverges](#static-instance-boot-replaces-laravels-container-scoping) |
| [`RemoveTestOnlyServicesPass`, removing every `sylament.test_only` service unless `framework.test` is on](../../../packages/support/src/DependencyInjection/Compiler/RemoveTestOnlyServicesPass.php) | — (no Filament source) | done | — | [semantics](#a-test-only-service-is-removed-unless-frameworktest-is-on) |
| [Asset registration](../../../packages/support/src/SylamentSupportBundle.php) | [`FilamentAsset::register([Js::make('support', …)])`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [semantics](#the-asset-prepend-is-guarded-and-the-built-directory-needs-only-stimulus-from-the-importmap) |
| [Stylesheet entry (`index.css`, `components.css`, `fonts.css`)](../../../packages/support/assets/src/styles/index.css) | [`css/index.css`](../../../vendor/filament/support/resources/css/index.css) | done | — | [color-manager.md](color-manager.md) |
| [Base layer (`base.css`)](../../../packages/support/assets/src/styles/base.css) | [`css/base.css`](../../../vendor/filament/support/resources/css/base.css) | done | — | [diverges](#the-base-layer-is-scoped-to-sy-and-adds-the-cloak-and-colour-scheme-rules) |
| [`uncloak()` helper](../../../packages/support/assets/src/utils/cloak.ts) | Alpine's `x-cloak` | done | — | [diverges](#the-base-layer-is-scoped-to-sy-and-adds-the-cloak-and-colour-scheme-rules) |
| [Eager `core` controller and its stylesheet autoimport](../../../packages/support/assets/src/controllers/core_controller.ts) | — | done | — | [semantics](#the-eager-core-controller-puts-the-toolkit-stylesheet-on-the-page) |
| [Translations (`hasTranslations`)](../../../packages/support/translations) | [`configurePackage()`](../../../vendor/filament/support/src/SupportServiceProvider.php), [`resources/lang/en/`](../../../vendor/filament/support/resources/lang/en) | done | — | [semantics](#the-catalogue-holds-the-keys-supports-code-reads) |
| Livewire request / component wiring | [`packageRegistered()`/`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | blocked | live-component-state work | [detail](#livewire-request-and-component-wiring-is-not-ported) |
| Blade directives & string macros | [`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | skipped | — | [diverges](#blade-directives-and-string-macros-are-not-ported) |
| PackageServiceProvider bootstrapping: config and views publishing, commands, `AboutCommand`, `optimizes` | [`configurePackage()`/`packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php) | skipped | — | [diverges](#packageserviceprovider-bootstrapping-is-replaced-by-abstractbundle-mechanics) |

## Details

### Livewire request and component wiring is not ported

The provider's `DataStore` override, `PartialsComponentHook`, the `originalRequest` scoped binding and the `ScopedComponentManager` clone are Livewire-runtime plumbing: request-scoped state, per-request component hooks and config isolation.
There is no Livewire here (the idiom translation is UX LiveComponent), and Filament's seams are not ported verbatim; but the equivalent live-component request wiring is wanted, so the row is blocked on the live-component-state work, where Sylament grows its own data store, component hooks and per-request scope.
Livewire → UX LiveComponent is a systematic [translation](../translations.md#idiom).

## Semantics

### The asset prepend is guarded, and the built directory needs only Stimulus from the importmap

[`SylamentBundle::prependAssetMapper()`](../../../packages/support/src/SylamentBundle.php), which `prependExtension()` calls here and in every bundle with an `assets/` workspace, registers `packages/support/assets/dist` into `framework.asset_mapper.paths` under the alias `@sylament/support`, but only when [`isAssetMapperAvailable()`](../../../packages/support/src/SylamentBundle.php) confirms `AssetMapperInterface` exists and FrameworkBundle ships `Resources/config/asset_mapper.php`, the guard that keeps `symfony/asset-mapper` an optional dependency for production consumers.
It is the Symfony analog of Filament registering `Js::make('support', …)` with `FilamentAsset`.
The guard and the prepend live on [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php), so its false branch, which returns without prepending, is one behaviour for every bundle that prepends, and its pin is one case derived over every `SylamentBundle` the kernel registers.
The prepend is checked where StimulusBundle's controllers-map generator checks it, by asking `AssetMapperInterface` for a built controller by source path and requiring the `@sylament/support` logical path back, so a prepend that is present but misaliased fails where an assertion on the compiled `framework.asset_mapper.paths` array would pass.

What lands in that directory is [`assets/scripts/build-js.mjs`](../../../packages/support/assets/scripts/build-js.mjs)'s business: every `src/**/*.ts` builds per file and unbundled, its import specifiers shipped verbatim for the consuming app's importmap to resolve, except the entries in that script's `BUNDLED_ENTRIES` table, the `dropdown` controller bundling `@floating-ui/dom` and the `sortable` controller bundling `sortablejs` ([sortable.md](sortable.md#sortablejs-is-bundled-twice-pinned-to-one-version)), which inline their library behind a licence banner composed from the installed package and keep `@hotwired/stimulus` external.
So the prepended directory needs no importmap entry beyond Stimulus itself.

### The eager `core` controller puts the toolkit stylesheet on the page

[`core_controller.ts`](../../../packages/support/assets/src/controllers/core_controller.ts) is an empty controller, registered `eager` and `enabled` under `symfony.controllers.core` in [`package.json`](../../../packages/support/assets/package.json), whose `autoimport` of `@sylament/support/styles/index.css` puts the whole toolkit stylesheet on the page and keeps `@sylament/support` in AssetMapper's eager import graph.
A lazy or disabled entry, or a moved autoimport path, drops every package's styles without an error, since nothing else loads the stylesheet.

### The colour policies are registered public

`loadExtension()` registers the managers, the Twig extensions and the `Components/*.php` glob, and the colour-policy glob under `Colors/Components/` stays public, because `ColorManager` resolves a policy from the container by class-string and a private service is not reachable through `Container::get()`.

### A test-only service is removed unless `framework.test` is on

A bundle registers a service only tests use in `loadExtension()` and tags it `sylament.test_only`, as actions' [`ExceptionRecorder`](../actions/testable.md#the-recorder-exists-only-when-frameworktest-is-on) and notifications' [`NotificationRecorder`](../notifications/tests-notifications.md#the-recorder-exists-only-when-frameworktest-is-on-and-answers-for-one-request) are, and support's `build()` adds `RemoveTestOnlyServicesPass`, which removes every tagged definition unless `test.service_container` is defined.
FrameworkBundle defines that service only under `framework.test`, and an extension's `load()` runs on a container of its own that never sees another extension's services, so test mode is read in a compiler pass.
The pass runs before optimisation, so a removed service is gone before the decorations are resolved and the listeners collected: `SessionNotifications` stays undecorated, and the exception recorder never listens.
Run after the decorations, the same removal would leave every consumer of `SessionNotifications` pointing at a service that no longer exists, and the container would not compile.
One pass serves every package, since each package that ships a test-only service requires support.
[`RemoveTestOnlyServicesPassTest`](../../../tests/Packages/Support/DependencyInjection/Compiler/RemoveTestOnlyServicesPassTest.php) compiles the suite's kernel in `test` and in `test_mode_off`, the environment that turns `framework.test` off and nothing else.

### The catalogue holds the keys support's code reads

[`sylament+intl-icu.en.yaml`](../../../packages/support/translations/sylament+intl-icu.en.yaml) is the `sylament` domain, Filament's `filament::` namespace, and carries Filament's `components/*` keys for the badge, breadcrumbs, copyable, modal, pagination, section and tabs texts support's templates and traits render.
Filament's `components/button` (`messages.uploading_file`), `components/input/one-time-code`, `loading` and `components/copyable.messages.failed` keys ship nowhere, because no ported code renders them: the button's upload state, the one-time-code input and the loading indicator's text are unported, and the copy failure is [a gap of the controller](copyable.md#a-failed-copy-shows-nothing-where-filament-says-could-not-copy).

## Divergences

### Static-instance boot replaces Laravel's container scoping

Filament registers its managers as Laravel `scoped()`/`singleton()` services and re-resolves the scoped `ComponentManager` on `app->booted` (and on Octane's `RequestReceived`).
Sylament has no request-scoped container, so the managers are plain services made statically reachable through the [static-instance pattern](../../../packages/support/src/DependencyInjection/StaticInstanceRegistry.php): `build()` adds `StaticInstancePass`, which tags every `StaticInstance` definition, and `boot()` calls `StaticInstanceRegistry::bootStaticInstances()`, which sets each tagged service as its class's static instance.

`StaticInstancePass` decides by `is_a($class, StaticInstance::class, true)`, which autoloads the class, and it swallows the `Throwable` that raises.
A bundle may register a service whose class extends an optional dependency that is not installed (LiveComponent's `form.live_collection` extends Symfony Form's `AbstractType`, so autoloading it fatals when `symfony/form` is absent), and a class that cannot load is never a `StaticInstance`, so skipping it is safe and keeps the pass from breaking unrelated bundles.

The `ScopedComponentManager` clone and the `RequestReceived` re-resolution have no analog and are dropped; `ScopedComponentManager` is [its own row](component-manager.md#the-scoped-component-manager-is-not-ported) in component-manager.md.

### The base layer is scoped to `.sy` and adds the cloak and colour-scheme rules

Filament's `base.css` gives every enabled `button` and `[role='button']` a pointer cursor and applies the reduced-motion override to every element on the page.
Support's [`base.css`](../../../packages/support/assets/src/styles/base.css) scopes both to `.sy` and its descendants, because the toolkit renders inside a host application whose own elements are not its to restyle, as its generated preflight is scoped the same way.
It adds two rules Filament has no counterpart for: `:root.dark { color-scheme: dark }`, so the user agent paints form-control internals and scrollbars from the class the theme switcher toggles, and the `sy-cloak` hide-before-init rule.
`sy-cloak` replaces Alpine's `x-cloak`: it is `display: none` only under `@media (scripting: enabled)`, so cloaked content stays reachable with JavaScript off, where `x-cloak` would hide it for good, and the owning controller removes the class through [`uncloak()`](../../../packages/support/assets/src/utils/cloak.ts) once its initial state is applied, as the section controller does; forms' repeater and schemas' tabs controllers import a `cloak.ts` copy of their own workspace.

### Blade directives and string macros are not ported

`packageBooted()` registers Blade directives (`@capture`, `@endcapture`, `@captureSlots`, `@filamentScripts`, `@filamentStyles`, and the `@trim` precompiler) and `Str` / `Stringable` macros (`sanitizeHtml`, `sanitizeUrl`, `sanitizeCssColor`, `ucwords`).
There is no Blade, since templating is Twig, and macro-based extension is a rejected pattern here (see [Macroable is not ported](component.md#macroable-is-not-ported)).
The Blade directives have no Twig equivalent; the sanitizer logic, where wanted, lands as helpers or Twig filters rather than string macros.
The three `ComponentAttributeBag` macros from the same method are ported as Twig functions and are not part of this divergence: `grid()` and `gridColumn()` on `GridExtension` and `colorAttributes()` on `ColorExtension` ([component-attribute-bag.md](component-attribute-bag.md#grid-and-gridcolumn-are-twig-functions-not-bag-methods)).
Of the four `Str` macros, `sanitizeCssColor` is a public method on the colour manager rather than a function in `helpers.php`, both of its callers being the manager's own emitters ([why](color-manager.md#the-css-colour-sanitizer-is-a-method-on-the-manager-and-drops-rather-than-nulls)), and `sanitizeUrl` is the pure [`sanitize_url()`](helpers.md) helper forms' `FileUpload` imports with `use function`, since it needs no service at all.
`sanitizeHtml` and `ucwords` are unported.

### PackageServiceProvider bootstrapping is replaced by AbstractBundle mechanics

Filament's provider extends Spatie's `PackageServiceProvider`: `configurePackage()` declares commands, a config file, translations and views, and `packageBooted()` publishes config, wires `AboutCommand` info and calls `optimizes()`.
None of these Laravel package-tooling affordances carries over, since Symfony handles the equivalents elsewhere: asset registration is the AssetMapper prepend above, configuration is Symfony bundle config, and console commands are auto-registered by tag when they exist.
The command classes are their own rows in the [package README](README.md#unported).
Translations need [no provider duty](../../internals/i18n.md#every-package-ships-its-catalogue-in-translations-which-loads-with-no-wiring), and an application overrides a key in its own `translations/` directory instead of publishing a copy.
