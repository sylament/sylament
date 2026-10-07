---
description: The `FilesystemRegistry` storage seam every file-handling component reaches through, the port's translation of Laravel's `Storage::disk()`.
ports:
  - vendor/filament/forms/src/Components/BaseFileUpload.php
  - vendor/filament/tables/src/Columns/ImageColumn.php
owns:
  - packages/support/src/Filesystem/
---
# FilesystemRegistry (support)

[`Sylament\Support\Filesystem\FilesystemRegistry`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) is the storage seam every file-handling component reaches through.
It is the `Storage::disk()` translation: Filament asks Laravel's `Storage` facade for a named disk and gets a `Filesystem` back; Sylament asks this registry for a named flysystem storage and gets a `League\Flysystem\FilesystemOperator` back.
There is no Filament class to diff against, since the facade is the original, so its shape is dictated by what its consumers need: a disk by name, a toolkit-wide default when a component names none, the expiry a temporary URL is minted with, and the URL itself.

The storages themselves are not the registry's.
`league/flysystem-bundle` compiles each `flysystem.storages.<name>` entry into a private `Filesystem` definition tagged `flysystem.storage` with a `storage` attribute; the registry is constructed with `tagged_locator('flysystem.storage', 'storage')`, the bundle's own convention and the locator its `PushCommand` and `PullCommand` take, so every configured storage is reachable by the name the application gave it, private definitions included.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get(?string $name = null)`: a storage by name, the default when null](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | `Storage::disk($name)` | done | — | [diverges](#the-registry-replaces-a-facade-and-keeps-the-disk-vocabulary) |
| [`get()` throws naming the storage, the registered names and how to register one](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | `Storage::disk()` on an unconfigured disk | done | — | [semantics](#an-unknown-storage-is-a-logicexception-not-an-empty-filesystem) |
| [`get()` refuses a tagged service that is not a `FilesystemOperator`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | — (Laravel's disk factory builds the object itself) | done | — | [semantics](#an-unknown-storage-is-a-logicexception-not-an-empty-filesystem) |
| [`has(string $name)`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | — | done | — | — |
| [`getNames()`: the registered storage names, sorted](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | — | done | — | — |
| [`getDefaultDiskName()`: throws naming the config key when unset](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | `config('filament.default_filesystem_disk')` | done | — | [semantics](#there-is-no-fallback-disk) |
| [`getTemporaryUrlExpiryMinutes()`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | [`config('filament.temporary_file_url_expiry_minutes', 30)`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-expiry-is-handed-out-in-minutes-and-the-caller-owns-the-rounding) |
| [`getFileUrl(FilesystemOperator $storage, string $path, ?string $visibility = null)`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | inline in [`BaseFileUpload::getUploadedFile()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) and [`ImageColumn::getImageUrl()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#getfileurl-mints-the-browser-facing-url) |
| [`sylament.default_filesystem_disk`: scalar, default null](../../../packages/support/src/SylamentSupportBundle.php) | [`config('filament.default_filesystem_disk')`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#there-is-no-fallback-disk) |
| [`sylament.temporary_file_url_expiry_minutes`: integer ≥ 1, default 30](../../../packages/support/src/SylamentSupportBundle.php) | [`config('filament.temporary_file_url_expiry_minutes', 30)`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-expiry-is-handed-out-in-minutes-and-the-caller-owns-the-rounding) |
| [The `flysystem.storage` tag contract](../../../packages/support/src/SylamentSupportBundle.php) | — | done | — | [semantics](#a-consumer-without-the-bundle-tags-its-own-operator) |

## Semantics

### `getFileUrl()` mints the browser-facing URL

One method holds the URL semantics both consumers share: forms' [`BaseFileUpload::getUploadedFileUrl()`](../forms/file-upload.md#visibility-defers-to-the-storage-and-a-private-file-never-falls-through-to-a-public-url) and tables' [`ImageColumn`](../tables/image-column.md) delegate to it, rather than each carrying a copy of the minting logic.
A null `$visibility` is read from the storage per file; an unreadable one is read as private; and the private arm returns `null` rather than falling through to `publicUrl()`, because otherwise a storage that cannot mint a temporary URL would hand the browser a public link to a file the user marked private.
`temporaryUrl()` and `publicUrl()` are both guarded by `method_exists()`, since neither is on `FilesystemOperator`, and by their `UnableToGenerate*` exceptions; a temporary URL expires `getTemporaryUrlExpiryMinutes()` from now ([the rounding note](#the-expiry-is-handed-out-in-minutes-and-the-caller-owns-the-rounding) carries the dropped `endOfHour()`); and every URL leaves through [`sanitize_url()`](helpers.md).
The operator is a parameter rather than resolved from a name, so the method adds no reach into flysystem symbols beyond what [the soft dependency](#leagueflysystem-is-suggested-rather-than-required) already covers: nothing runs it without an operator in hand.

### An unknown storage is a `LogicException`, not an empty filesystem

Two refusals, both eager.
A name the locator does not know throws a `LogicException` that names the storage, lists every registered name (or `none`) and states both ways to register one: `flysystem.storages.<name>` with league/flysystem-bundle, or the `flysystem.storage` tag with a `storage` attribute.
Without the bundle the locator is empty, so the first upload fails with that message rather than with a container error naming a service id the reader never wrote.

The second refusal is the `instanceof FilesystemOperator` check on the way out.
The tag is a plain string anyone may stamp, and `ServiceLocator::get()` is typed `mixed`, so a mis-tagged service would otherwise surface as a `TypeError` several frames inside a component that only asked for a disk.
Naming the tag and the expected interface at the seam turns that into a five-second fix.

### There is no fallback disk

`get()` with no name asks `getDefaultDiskName()`, which throws when `sylament.default_filesystem_disk` is unset rather than guessing at a storage, even when exactly one is registered.
Guessing would make the number of configured storages change where a component writes, so adding a second disk to an application would silently relocate every upload that never named one.
An application therefore needs two things: at least one service carrying the `flysystem.storage` tag, through league/flysystem-bundle or [a tag by hand](#a-consumer-without-the-bundle-tags-its-own-operator), and `sylament.default_filesystem_disk` naming one of them, without which every component that names no disk of its own throws on first use, naming the key.

### The expiry is handed out in minutes, and the caller owns the rounding

`getTemporaryUrlExpiryMinutes()` returns the number, not an expiry instant, which is what Filament's call sites read: `now()->addMinutes(config('filament.temporary_file_url_expiry_minutes', 30))->endOfHour()` in both [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) and [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php).
The `endOfHour()` rounding is deliberate on their side, making the signed URL stable within the hour so a page can be cached, and it belongs with the caller that decides how long a link may live in a page, not with the registry that only carries the setting.
A registry returning a `DateTimeImmutable` would have to guess that rounding for every consumer.
The shared caller, [`getFileUrl()`](#getfileurl-mints-the-browser-facing-url), mints a plain `+N minutes` with no rounding, so a signed URL differs per request, and a consumer wanting Filament's within-the-hour stability builds its own instant from this getter.

### A consumer without the bundle tags its own operator

`league/flysystem-bundle` is a convenience, not a requirement.
An application that builds its own `FilesystemOperator` service reaches the registry by tagging it `flysystem.storage` with a `storage` attribute holding the name components will ask for:

```yaml
services:
    app.uploads_storage:
        class: League\Flysystem\Filesystem
        arguments: ['@app.uploads_adapter']
        tags:
            - { name: 'flysystem.storage', storage: 'public.storage' }
```

That is the whole contract: the registry never touches the bundle's own services, only the tag.

## Divergences

### The registry replaces a facade and keeps the `disk()` vocabulary

Filament reaches storage through the `Storage` facade, a Laravel global resolved out of the container at each call site.
There is no facade layer here, so the seam is an injected service; `sylament/forms` passes it to its file upload by service reference.

What does not change is the word.
Filament's public API says `disk()`, as in `FileUpload::make('x')->disk('s3')`, and every migrating user greps for it, so the setter keeps its name even though the value it takes is a flysystem storage name rather than a Laravel disk key.
The idiom translation is recorded in [translations.md](../translations.md#idiom); nothing in the user-facing API is renamed.

### `league/flysystem` is suggested rather than required

The registry imports `League\Flysystem\FilesystemOperator` in `src/`, which by the dependency-declaration rule in the root `CLAUDE.md` reads like a `require`.
It stays a `suggest` because the import is a guarded optional integration of the shape `support` ships for AssetMapper: nothing inside `support` constructs this service, so in an application without flysystem the definition is never instantiated and the symbol is never resolved.
The consumer that needs the library, `sylament/forms`, declares `league/flysystem: ^3` itself.

The cost of a softer link is that no Composer-level check catches a break, so the seam is pinned by a test instead.
[`SylamentSupportBundleTest`](../../../packages/support/tests/Integration/SylamentSupportBundleTest.php) boots the real `FlysystemBundle` (a root dev dependency) with two `local` storages and asserts the registry collects both by name, so a change to the tag name or its index attribute upstream fails that test rather than the seam quietly returning nothing.
