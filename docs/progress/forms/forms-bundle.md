# SylamentFormsBundle (forms)

[`Sylament\Forms\SylamentFormsBundle`](../../../packages/forms/src/SylamentFormsBundle.php)
— the forms bundle class. It ports Filament's
[`FormsServiceProvider`](../../../vendor/filament/forms/src/FormsServiceProvider.php)
(Spatie's `PackageServiceProvider` lifecycle becomes a Symfony `AbstractBundle`
via [`SylamentBundle`](../../../packages/support/src/SylamentBundle.php)). Today
the class is a bare `extends SylamentBundle` stub with an empty body: it registers
the bundle and inherits the `@SylamentForms` Twig namespace by
AbstractBundle/TwigBundle convention, which is all the shipped field surface
needs — the fields render through schemas'/support's Twig extensions, and
`FormsServiceProvider` binds no container services, so there is nothing to wire in
a `loadExtension()`. Asset registration, `Make*` commands, translations, the
`table-select` Livewire component, and the `Testable` mixins are not ported yet.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/FormsServiceProvider.php`](../../../vendor/filament/forms/src/FormsServiceProvider.php)
  — the provider whose `configurePackage()` / `packageBooted()` this bundle ports

## Surface

Every provider responsibility. _Blocked by_ names what has to land first.
_Detail_ carries a **detail** link to a [Details](#details) write-up, a
**diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle registration + `@SylamentForms` view namespace](../../../packages/forms/src/SylamentFormsBundle.php) | [`->hasViews()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | done | — | [diverges](#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call) |
| Asset registration (12 field AlpineComponents) | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | [detail](#asset-registration) |
| `Make*` commands + stub publishing | [`configurePackage()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | [detail](#make-commands--stub-publishing) |
| Translations (`hasTranslations`) | [`configurePackage()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | todo | — | [detail](#translations) |
| `Livewire::component('table-select', …)` | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | blocked | TableSelect field feature | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |
| `Testable::mixin(new TestsForms)` / `TestsFormComponentActions` | [`packageBooted()`](../../../vendor/filament/forms/src/FormsServiceProvider.php) | skipped | — | [diverges](#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

The bundle-load + view-namespace row is covered indirectly:
[`LiveFieldTest`](../../../packages/forms/tests/Integration/LiveFieldTest.php)
boots [`tests/Fixtures/Kernel.php`](../../../packages/forms/tests/Fixtures/Kernel.php)
(which registers `SylamentFormsBundle`) and renders `TextInput`/`Textarea` through
a live component, and
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php)
/ [`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php)
render the field templates — proving the bundle loads and its
`@SylamentForms/components/...` templates resolve. The unported responsibilities
have no coverage because they have no code.

## Details

### Asset registration

Filament's `packageBooted()` calls `FilamentAsset::register([...],
'filament/forms')` with 12 field `AlpineComponent`s (`checkbox-list`,
`code-editor`, `color-picker`, `date-time-picker`, `file-upload`, `key-value`,
`markdown-editor`, `rich-editor`, `select`, `slider`, `tags-input`, `textarea`).
Sylament's translation is a `prependExtension()` that registers
`packages/forms/assets/dist` as `@sylament/forms` (mirroring
[`SylamentSupportBundle::prependAssetMapper()`](../../../packages/support/src/SylamentSupportBundle.php)).
Nothing is wired: the bundle has no `prependExtension()` override and
`packages/forms/assets/dist` does not exist (only `assets/src/styles`).
**Next step:** add the `prependExtension()` registration as each field's JS
component is ported — deferred until those components land.

### Make* commands + stub publishing

`MakeRichContentCustomBlockCommand` / `MakeFieldCommand` / `MakeFormCommand` /
`MakeLivewireFormCommand` and the `stubs/` publishing loop are unported — the port
has no `Commands/` subtree. **Next step:** port when the `Commands/` subtree is
tackled (tracked as the `Commands / Make*` and `Commands / FileGenerators` todo
rows in the [package README](README.md)).

### Translations

`->hasTranslations()` registers Filament's `resources/lang` namespace. Unported.
**Next step:** port when the translations subtree is tackled.

## Divergences

### The view namespace is registered by Symfony convention, not by an explicit provider call

Filament calls `->hasViews()` to register its Blade namespace. Sylament registers
nothing in the bundle file (its body is a bare `//`): `AbstractBundle::getPath()`
returns `packages/forms`, and TwigBundle auto-maps `packages/forms/templates` →
`@SylamentForms`. The wiring is therefore invisible in the bundle — intentional,
and the reason the field classes can set `$template =
'@SylamentForms/partials/TextInput/index.html.twig'` with no explicit twig path
config. Same divergence as
[schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call).

### Livewire-specific provider responsibilities are dropped, not ported

`packageBooted()` registers the `table-select` Livewire component
(`Livewire::component('table-select', TableSelectLivewireComponent::class)`) and
two `Testable::mixin()` calls (`TestsForms`, `TestsFormComponentActions`). The
Testable mixins are a deliberate non-goal (**skipped** — Sylament tests are
Pest/`KernelTestCase`, not Livewire `Testable`;
[`LiveFieldTest`](../../../packages/forms/tests/Integration/LiveFieldTest.php)
drives fields through UX LiveComponent's `InteractsWithLiveComponents` instead).
The `table-select` registration is **blocked** on the TableSelect field feature
landing (the field, its `ModalTableSelect`, and a UX LiveComponent host
equivalent), not skipped — it is wanted for parity once table-select exists. Both
are tracked as their own rows in the [package README](README.md) supporting
surface (`TableSelectLivewireComponent`, `TestsForms`,
`TestsFormComponentActions`).

_The `FormsServiceProvider` → `SylamentFormsBundle` swap (and the underlying
Spatie `PackageServiceProvider` → Symfony `AbstractBundle` move) is a systematic
Symfony-idiom translation, like those the [rename table](../renames.md) collects
under "Not renames" — Laravel package tooling with no per-name Sylament equivalent
to enumerate — not a per-item rename or divergence._
