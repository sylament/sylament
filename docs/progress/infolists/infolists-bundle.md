---
description: The infolists bundle class and the package's stylesheet entry point.
ports:
  - vendor/filament/infolists/src/InfolistsServiceProvider.php
  - vendor/filament/infolists/src/Commands/MakeEntryCommand.php
  - vendor/filament/infolists/resources/css/index.css
owns:
  - packages/infolists/assets/src/styles/index.css
  - packages/infolists/src/SylamentInfolistsBundle.php
---
# SylamentInfolistsBundle (infolists)

[`Sylament\Infolists\SylamentInfolistsBundle`](../../../packages/infolists/src/SylamentInfolistsBundle.php) ports Filament's [`InfolistsServiceProvider`](../../../vendor/filament/infolists/src/InfolistsServiceProvider.php) as a subclass of support's [`SylamentBundle`](../support/support-bundle.md).
[`index.css`](../../../packages/infolists/assets/src/styles/index.css) is the package's stylesheet entry point, which support's [`index.css`](../../../packages/support/assets/src/styles/index.css) imports into its single built stylesheet.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Bundle class](../../../packages/infolists/src/SylamentInfolistsBundle.php) | [`InfolistsServiceProvider.php`](../../../vendor/filament/infolists/src/InfolistsServiceProvider.php) | in progress | — | [detail](#the-bundle-class-is-an-empty-subclass-that-no-test-boots) |
| [Stylesheet entry point](../../../packages/infolists/assets/src/styles/index.css) | [`resources/css/index.css`](../../../vendor/filament/infolists/resources/css/index.css) | in progress | — | [detail](#the-stylesheet-entry-point-imports-no-component-sheet) |
| `@SylamentInfolists` template namespace | [`->hasViews()`](../../../vendor/filament/infolists/src/InfolistsServiceProvider.php) | todo | — | [detail](#there-is-no-template-namespace-because-the-package-has-no-templates-directory) |
| `MakeEntryCommand` + stub publishing | [`Commands/MakeEntryCommand.php`](../../../vendor/filament/infolists/src/Commands/MakeEntryCommand.php), [`packageBooted()`](../../../vendor/filament/infolists/src/InfolistsServiceProvider.php) | todo | — | — |
| Translations (`->hasTranslations()`) | [`configurePackage()`](../../../vendor/filament/infolists/src/InfolistsServiceProvider.php) | todo | — | [i18n](../../internals/i18n.md#translation-is-one-deferred-pass-not-a-per-component-port) |
| `Testable::mixin(new TestsInfolistActions)` | [`packageBooted()`](../../../vendor/filament/infolists/src/InfolistsServiceProvider.php) | skipped | — | [diverges](../actions/actions-bundle.md#livewire-specific-provider-responsibilities-are-dropped-not-ported) |

## Details

### The bundle class is an empty subclass that no test boots

`SylamentInfolistsBundle` declares no member, so it carries only what `AbstractBundle` and `SylamentBundle` give every bundle.
The package's test kernel registers it, but the suite's one test asserts `true` without booting that kernel, so nothing shows the bundle loads.
What is missing is a test that boots the kernel with the bundle registered.

### The stylesheet entry point imports no component sheet

Filament's `index.css` imports `entry.css` and one sheet per entry type under `layer(components)`.
The port's file holds only a comment, because no infolist entry exists to style.
Support's `index.css` already imports it, so a sheet imported here reaches the built stylesheet with no further wiring.

### There is no template namespace, because the package has no `templates/` directory

Filament registers its view namespace with `->hasViews()`.
The port relies on TwigBundle's convention instead ([schemas-bundle.md](../schemas/schemas-bundle.md#the-view-namespace-is-registered-by-symfony-convention-not-by-an-explicit-provider-call)), and that convention maps a bundle's `templates/` directory to its namespace only when the directory exists.
`packages/infolists/` has no `templates/` directory, so `@SylamentInfolists` resolves nothing.

## Divergences

### An entry is a schemas component, not a second tree beside the schema

Filament's [`Entry`](../../../vendor/filament/infolists/src/Components/Entry.php) extends `Filament\Schemas\Components\Component`: an entry is a schema component reading schema state.
The port's entries extend schemas' [`Component`](../schemas/component.md) the same way when they land, so an infolist is a `Schema` and no parallel component tree exists.

### `TextEntry` shares the column's formatting trait instead of porting the infolists copy

Filament's `TextEntry` composes `Infolists\Components\Concerns\CanFormatState`, a parallel copy of the formatting catalogue tables' `Columns\Concerns\CanFormatState` carries.
The port's `TextEntry`, when it lands, composes the catalogue [`TextColumn`](../tables/text-column.md) uses (badge, colours, icons, size, weight and font, copyable, date and money, lists, `html()` and `markdown()`) rather than a third copy beside the column's and the summarizer's.

### `CodeEntry` waits on a syntax-highlighting vendor

Filament's [`CodeEntry`](../../../vendor/filament/infolists/src/Components/CodeEntry.php) highlights through Phiki (`Phiki\Phiki`), a vendor outside the pre-approved `symfony/*` packages.
It stays unported, and blocked in the package README, until a highlighting vendor is signed off.
