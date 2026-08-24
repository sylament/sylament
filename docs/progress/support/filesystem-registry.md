# FilesystemRegistry (support)

[`Sylament\Support\Filesystem\FilesystemRegistry`](../../../packages/support/src/Filesystem/FilesystemRegistry.php)
— the storage seam every file-handling component reaches through. It is the
`Storage::disk()` translation: Filament asks Laravel's `Storage` facade for a
named disk and gets a `Filesystem` back; Sylament asks this registry for a named
**flysystem storage** and gets a `League\Flysystem\FilesystemOperator` back.
There is no Filament class to diff against — the facade *is* the original — so
this is a Sylament-only file whose shape is dictated by what the components
consuming it need: a disk by name, a toolkit-wide default when a component names
none, and the expiry a temporary URL is minted with.

The storages themselves are not ours. `league/flysystem-bundle` compiles each
`flysystem.storages.<name>` entry into a private `Filesystem` definition tagged
`flysystem.storage` with a `storage` attribute; the registry is constructed with
`tagged_locator('flysystem.storage', 'storage')` — the bundle's own convention,
the same locator its `PushCommand` and `PullCommand` take — so every configured
storage is reachable by the name the application gave it, private definitions
included.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — there is no Filament source; these are what it translates:

- `Illuminate\Support\Facades\Storage::disk()` — the call Filament's
  [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php)
  makes for every read, write and delete
- [`vendor/league/flysystem-bundle/src/DependencyInjection/FlysystemExtension.php`](../../../vendor/league/flysystem-bundle/src/DependencyInjection/FlysystemExtension.php)
  — where the `flysystem.storage` tag is stamped and where the same
  `tagged_locator` shape is used

## Surface

Every point of the registry and the configuration behind it. _Blocked by_ names
what has to land first. _Detail_ carries **diverges** → the permanent note in
[Divergences](#divergences), and **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`get(?string $name = null)` — a storage by name, the default when null](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | `Storage::disk($name)` | done | — | [diverges](#the-registry-replaces-a-facade-and-keeps-the-disk-vocabulary) |
| [`get()` throws naming the storage, the registered names and how to register one](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | `Storage::disk()` on an unconfigured disk (`InvalidArgumentException: Disk [x] does not have a configured driver.`) | done | — | [semantics](#an-unknown-storage-is-a-logicexception-not-an-empty-filesystem) |
| [`get()` refuses a tagged service that is not a `FilesystemOperator`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | — (Laravel's disk factory builds the object itself) | done | — | [semantics](#an-unknown-storage-is-a-logicexception-not-an-empty-filesystem) |
| [`has(string $name)`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | — | done | — | — |
| [`getNames()` — the registered storage names, sorted](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | — | done | — | — |
| [`getDefaultDiskName()` — throws naming the config key when unset](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | `config('filament.default_filesystem_disk')` | done | — | [semantics](#there-is-no-fallback-disk) |
| [`getTemporaryUrlExpiryMinutes()`](../../../packages/support/src/Filesystem/FilesystemRegistry.php) | [`config('filament.temporary_file_url_expiry_minutes', 30)`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-expiry-is-handed-out-in-minutes-and-the-caller-owns-the-rounding) |
| [`sylament.default_filesystem_disk` — scalar, default null](../../../packages/support/src/SylamentSupportBundle.php) | [`config('filament.default_filesystem_disk')`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [configuration.md](../../configuration.md#storage) |
| [`sylament.temporary_file_url_expiry_minutes` — integer ≥ 1, default 30](../../../packages/support/src/SylamentSupportBundle.php) | [`config('filament.temporary_file_url_expiry_minutes', 30)`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [configuration.md](../../configuration.md#storage) |
| [The `flysystem.storage` tag contract](../../../packages/support/src/SylamentSupportBundle.php) | — | done | — | [semantics](#a-consumer-without-the-bundle-tags-its-own-operator) |
| Per-disk URL generation (`publicUrl()` / `temporaryUrl()`) | `Storage::disk()->url()` / `temporaryUrl()` | skipped | — | Not the registry's job: those are methods on the operator flysystem already hands back, and only the caller knows whether the file it is describing is public. `forms`' `BaseFileUpload` calls them directly on the operator this returns |

## Semantics

### An unknown storage is a `LogicException`, not an empty filesystem

Two refusals, both eager. A name the locator does not know throws a
`LogicException` that names the storage, lists every registered name (or `none`)
and states both ways to register one — `flysystem.storages.<name>` with
league/flysystem-bundle, or the `flysystem.storage` tag with a `storage`
attribute. Without the bundle the locator is simply empty, so the very first
upload fails with that message rather than with a container error naming a
service id the reader never wrote.

The second refusal is the `instanceof FilesystemOperator` check on the way out.
The tag is a plain string anyone may stamp, and `ServiceLocator::get()` is typed
`mixed`, so a mis-tagged service would otherwise surface as a `TypeError` several
frames inside a component that only asked for a disk. Naming the tag and the
expected interface at the seam is the difference between a five-second fix and a
stack trace read backwards.

### There is no fallback disk

`get()` with no name asks `getDefaultDiskName()`, which throws when
`sylament.default_filesystem_disk` is unset rather than guessing at a storage —
even when exactly one is registered. Guessing would make the *number* of
configured storages change where a component writes, so adding a second disk to
an application would silently relocate every upload that never named one.

### The expiry is handed out in minutes, and the caller owns the rounding

`getTemporaryUrlExpiryMinutes()` returns the number, not an expiry instant, which
is exactly what Filament's call sites read —
`now()->addMinutes(config('filament.temporary_file_url_expiry_minutes', 30))->endOfHour()`
in both [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php)
and [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php).
The `endOfHour()` rounding is deliberate on their side (it makes the signed URL
stable within the hour so a page can be cached), and it belongs with the caller
that decides how long a link is allowed to live in a page, not with the registry
that only carries the setting. A registry returning a `DateTimeImmutable` would
have to guess that rounding for every consumer.

### A consumer without the bundle tags its own operator

`league/flysystem-bundle` is a convenience, not a requirement. An application
that builds its own `FilesystemOperator` service reaches the registry by tagging
it `flysystem.storage` with a `storage` attribute holding the name components
will ask for:

```yaml
services:
    app.uploads_storage:
        class: League\Flysystem\Filesystem
        arguments: ['@app.uploads_adapter']
        tags:
            - { name: 'flysystem.storage', storage: 'public.storage' }
```

That is the whole contract — the registry never touches the bundle's own
services, only the tag.

## Divergences

### The registry replaces a facade and keeps the `disk()` vocabulary

Filament reaches storage through the `Storage` facade, a Laravel global resolved
out of the container at each call site. There is no facade layer here, so the
seam is an injected service; `sylament/forms` autowires it by class-string.

What does **not** change is the word. Filament's public API says `disk()`
— `FileUpload::make('x')->disk('s3')` — and every migrating user greps for it, so
the setter keeps its name even though the value it takes is a *flysystem storage
name* rather than a Laravel disk key. The idiom translation is recorded in
[renames.md](../renames.md#idiom); nothing in the user-facing API is renamed.

### `league/flysystem` is suggested rather than required

The registry imports `League\Flysystem\FilesystemOperator` in `src/`, which by
the [dependency-declaration rule](../../../CLAUDE.md) reads like a `require`.
It stays a `suggest` because the import is a **guarded optional integration** of
the shape `support` already ships for AssetMapper: nothing inside `support`
constructs this service, so in an application without flysystem the class is
never loaded, the definition is never instantiated, and the symbol is never
resolved. The first consumer is `sylament/forms`, which declares
`league/flysystem: ^3` itself — so the package that actually needs the library is
the package that requires it.

The cost of a softer link is the same as for the other guarded soft deps: no
composer-level check catches a break, so the seam is pinned by a test instead.
[`SylamentSupportBundleTest`](../../../packages/support/tests/Integration/SylamentSupportBundleTest.php)
boots the real `FlysystemBundle` (a root dev dependency) with two `local`
storages and asserts the registry collects both by name — if the tag name or its
index attribute ever changes upstream, that test fails rather than the seam
quietly returning nothing.

## Consumer setup

What the registry needs from an application is two things, and only two: at
least one service carrying the `flysystem.storage` tag — `league/flysystem-bundle`
is the usual route, [a tag by hand](#a-consumer-without-the-bundle-tags-its-own-operator)
the other — and `sylament.default_filesystem_disk` naming one of them, without
which every component that does not name a disk of its own throws on first use,
naming the key.

The consumer-facing how-to is not repeated here:
[installation.md § 5](../../installation.md#5-storage-for-file-uploads) walks
through installing the bundle, defining a storage and pointing the toolkit at
it, and [configuration.md § Storage](../../configuration.md#storage) documents
the keys, the per-component override and what private files cost on a `local`
adapter.
