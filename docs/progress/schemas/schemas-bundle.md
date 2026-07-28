# SylamentSchemasBundle (schemas)

[`Sylament\Schemas\SylamentSchemasBundle`](../../../packages/schemas/src/SylamentSchemasBundle.php)
— the schemas bundle class. It ports Filament's
[`SchemasServiceProvider`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php)
(recorded rename `SchemasServiceProvider` → `SylamentSchemasBundle`; Spatie's
`PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle` via
[`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)). Today it
wires the two services the ported surface needs — the
[`SchemaExtension`](schema-extension.md) Twig extension and the state
[`PropertyAccessor`](property-accessor.md) — and inherits the `@SylamentSchemas`
Twig namespace by AbstractBundle/TwigBundle convention. Asset registration,
`Make*` commands, translations, and the file-upload security hook are not ported
yet.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/SchemasServiceProvider.php`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php)
  — the provider whose `configurePackage()` / `packageBooted()` this bundle ports

## Surface

Every provider responsibility. _Blocked by_ names what has to land first.
_Detail_ carries a **detail** link to a [Details](#details) write-up, a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [SchemaExtension → `twig.extension`](../../../packages/schemas/src/SylamentSchemasBundle.php) | [`configurePackage()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | done | — | — |
| [state PropertyAccessor service](../../../packages/schemas/src/SylamentSchemasBundle.php) | _(Sylament-invented, no provider source)_ | done | — | [diverges](#the-bundle-wires-a-state-propertyaccessor-that-filaments-provider-never-had) |
| [`@SylamentSchemas` view namespace](../../../packages/schemas/src/SylamentSchemasBundle.php) | [`->hasViews()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| Asset registration (schemas JS + actions/tabs/wizard AlpineComponents) | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | todo | — | [detail](#asset-registration) |
| `Make*` commands + stub publishing | [`configurePackage()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | todo | — | [detail](#make-commands--stub-publishing) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | todo | — | [detail](#translations) |
| File-upload restriction hook (`on('call')` abort) | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | blocked | file-upload feature | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |
| `Testable::mixin(new TestsSchemas)` | [`packageBooted()`](../../../vendor/filament/schemas/src/SchemasServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

Both service registrations are covered:
[`SylamentSchemasBundleTest.php`](../../../packages/schemas/tests/Integration/SylamentSchemasBundleTest.php)
asserts the container's twig service exposes the `renderSchema` function (proving
the extension registration + `twig.extension` tag), and
[`PropertyAccessorBootTest.php`](../../../packages/schemas/tests/Integration/State/PropertyAccessorBootTest.php)
boots the accessor as a static instance. The unported responsibilities have no
coverage because they have no code.

## Details

### Asset registration

Filament's `packageBooted()` calls `FilamentAsset::register([...])` with the
schemas `index.js` plus `actions`/`tabs`/`wizard` `AlpineComponent`s. Sylament's
translation is a `prependExtension()` that registers `packages/schemas/assets/dist`
as `@sylament/schemas` (mirroring
[`SylamentSupportBundle::prependAssetMapper()`](../../../packages/support/src/SylamentSupportBundle.php)).
Nothing is wired: the bundle has no `prependExtension()` override and
`packages/schemas/assets/dist` does not exist. **Next step:** add the
`prependExtension()` registration when schemas grows built JS — deferred until
those JS components are ported.

### Make* commands + stub publishing

`MakeComponentCommand` / `MakeLivewireSchemaCommand` / `MakeSchemaCommand` and
the `stubs/` publishing loop are unported. **Next step:** port when the
`Commands/` subtree is tackled (tracked as a todo row in the README supporting
surface).

### Translations

`->hasTranslations()` registers Filament's `resources/lang` namespace. Unported.
**Next step:** port when the translations subtree is tackled.

## Divergences

### The bundle wires a state PropertyAccessor that Filament's provider never had

`SylamentSchemasBundle` registers `sylament_schemas.property_accessor` (aliased to
[`State\PropertyAccessor`](../../../packages/schemas/src/State/PropertyAccessor.php))
— a service with no counterpart in `SchemasServiceProvider`. It exists because
Sylament reads and writes component state through Symfony's PropertyAccess
component instead of Livewire's data entanglement. This is bundle-visible fallout
of the state-layer divergence; the accessor itself is audited under
[property-accessor.md](property-accessor.md).

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace. Sylament registers
nothing in the bundle file: `AbstractBundle::getPath()` returns
`packages/schemas`, and TwigBundle auto-maps `packages/schemas/templates` →
`@SylamentSchemas`. The wiring is therefore invisible in the bundle — intentional,
and the reason component templates can reference `@SylamentSchemas/...` with no
explicit twig path config.

### `sylament/actions` becomes a hard composer requirement

`packages/schemas/composer.json` now requires `sylament/actions` — the first
requirement this package declares beyond `sylament/support`. It lands together
with the reverse edge (`actions` requiring `sylament/schemas`, for
[`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php)'s
direct `Schemas\Components\Component`/`Schemas\Schema` type-hints — see
[actions-bundle.md](../actions/actions-bundle.md#every-package-declares-what-its-own-source-needs-transitives-notwithstanding))
in the same commit, ahead of the schemas-side code that goes on to import
`Action`/`ActionGroup` throughout the rest of the schemas↔actions integration
epic. Filament's own graph already has this cycle — `filament/schemas` requires
`filament/actions` directly, `filament/actions` reaches back to
`filament/schemas` only transitively, through `filament/forms` — so this is not
a new coupling, just the direct form of an edge Filament already has, declared
here because the three intermediate packages don't exist in this port yet.

### Livewire-specific provider responsibilities are dropped, not ported

The provider's `Testable::mixin(new TestsSchemas)` and the `on('call', ...)` hook
that aborts unauthorized `_startUpload` / `_finishUpload` / `_uploadErrored` /
`_removeUpload` calls are Livewire-runtime concerns. The Testable mixin is a
deliberate non-goal (**skipped** — Sylament tests are Pest/`KernelTestCase`, not
Livewire `Testable`). The file-upload security hook is **blocked** on the
file-upload feature landing (`RestrictsFileUploadsToSchemaComponents` plus a
`FileUpload` field), not skipped — it is wanted for parity once uploads exist.

_See also: the global [rename table](../renames.md) — the
`SchemasServiceProvider` → `SylamentSchemasBundle` rename and the Spatie
`PackageServiceProvider` → Symfony `AbstractBundle` idiom translation are
recorded there._
