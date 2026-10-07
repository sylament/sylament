---
description: The `ImageColumn`, which renders its cell state as `<img>` elements from URLs, `data:` URIs or storage paths the filesystem registry mints URLs for.
ports:
  - vendor/filament/tables/src/Columns/ImageColumn.php
  - vendor/filament/tables/resources/css/columns/image.css
owns:
  - packages/tables/assets/src/styles/components/image-column.css
  - packages/tables/src/Columns/ImageColumn.php
  - packages/tables/templates/partials/ImageColumn/
---
# ImageColumn (tables)

[`Sylament\Tables\Columns\ImageColumn`](../../../packages/tables/src/Columns/ImageColumn.php) is a [`Column`](column.md) that renders its cell state as one or more `<img>` elements: a plain URL or `data:` URI as-is, a storage path through [`FilesystemRegistry`](../support/filesystem-registry.md)'s URL minting.
It ports Filament's [`Filament\Tables\Columns\ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php).
[`column.md`](column.md) tracks what every column carries.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`disk()` / `getDiskName()` / `getDisk()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::disk()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#the-disk-comes-from-the-registry-and-a-new-built-column-throws-for-one) |
| [`visibility()` / `getVisibility()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::visibility()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#url-minting-is-the-registrys-and-visibility-defers-to-the-storage) |
| `getCustomVisibility()` | [`ImageColumn::getCustomVisibility()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | skipped | — | [diverges](#url-minting-is-the-registrys-and-visibility-defers-to-the-storage) |
| [`getImageUrl()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::getImageUrl()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#url-minting-is-the-registrys-and-visibility-defers-to-the-storage) · [diverges](#every-emitted-url-passes-sanitize_url-the-passthrough-included) |
| [`checkFileExistence()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::checkFileExistence()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#turning-the-existence-check-off-does-not-stop-the-visibility-read) |
| [`imageSize()` / `imageHeight()` / `imageWidth()` / `getImageHeight()` / `getImageWidth()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | — |
| [`circular()` / `square()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#square-emits-no-class) |
| [`alt()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::alt()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | — |
| [`defaultImageUrl()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::defaultImageUrl()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#every-emitted-url-passes-sanitize_url-the-passthrough-included) |
| [`stacked()` / `overlap()` / `ring()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | — |
| [`limit()` / `limitedRemainingText()` / `limitedRemainingTextSize()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#a-zero-or-negative-limit-is-unlimited) |
| [`extraImgAttributes()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::extraImgAttributes()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | in progress | — | [detail](#merging-the-same-img-attribute-closure-twice-registers-it-twice) · [semantics](#extra-img-attribute-registrations-merge-defaults-first) |
| [`wrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | — |
| [`getStateItems()` / `getStateOverLimitCount()` — the cell's image list](../../../packages/tables/src/Columns/ImageColumn.php) | — (Sylament-only) | done | — | [semantics](#getstateitems-resolves-urls-in-php-not-in-the-template) · [diverges](#a-non-string-state-value-falls-to-the-default-image) |
| [`$template`](../../../packages/tables/src/Columns/ImageColumn.php) + [the partial it names](../../../packages/tables/templates/partials/ImageColumn/index.html.twig) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [text-column.md](text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) |
| [`getLimitedRemainingTextSizeClass()`](../../../packages/tables/src/Columns/ImageColumn.php) | — (Sylament-only) | done | — | [diverges](#the-sy-size-base-css-arm-is-not-ported) |
| `size()` — deprecated alias | [`ImageColumn::size()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | skipped | — | [deprecated: use `imageSize()`](../renames.md#a-deprecated-filament-member-is-not-ported) |
| `height()` / `getHeight()` — deprecated aliases | [`ImageColumn::height()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | skipped | — | [deprecated: use `imageHeight()` / `getImageHeight()`](../renames.md#a-deprecated-filament-member-is-not-ported) |
| `rounded()` / `isRounded()` — deprecated aliases | [`ImageColumn::rounded()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | skipped | — | [deprecated: use `circular()` / `isCircular()`](../renames.md#a-deprecated-filament-member-is-not-ported) |
| [The tooltip arms — `title` per image from `getTooltip($stateItem)`, and on the blank root from `getEmptyTooltip()`](../../../packages/tables/templates/partials/ImageColumn/index.html.twig) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [column.md](column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) |
| [The link arm — `getUrl($stateItem)` wrapping each image in an `<a>`](../../../packages/tables/templates/partials/ImageColumn/index.html.twig) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [column.md](column.md#geturl-answers-by-argument-count-so-a-closures-parameter-name-changes-the-dom) |
| [The blank-state placeholder (`getPlaceholder()`)](../../../packages/tables/templates/partials/ImageColumn/index.html.twig) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#a-default-image-beats-the-placeholder) |
| [`sy-inline` on the root, and the padding behind `:not(.sy-inline)`](../../../packages/tables/assets/src/styles/components/image-column.css) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php), [`columns/image.css`](../../../vendor/filament/tables/resources/css/columns/image.css) | done | — | [color-column.md](color-column.md#the-stacked-on-mobile-rule-has-nothing-to-select) |
| [The wrapper `extraAttributes()` on the root div](../../../packages/tables/templates/partials/ImageColumn/index.html.twig) | [`Column::getExtraAttributeBag()`](../../../vendor/filament/tables/src/Columns/Column.php) | done | — | — |

## Details

### Merging the same img-attribute closure twice registers it twice

Filament's `extraImgAttributes($attributes, merge: true)` returns early when `$attributes` is a `Closure` already in the list (`in_array(…, strict: true)`), so registering one closure instance again changes nothing.
The port appends unconditionally, as support's `extraAttributes()` does ([has-extra-attributes.md](../support/has-extra-attributes.md#merging-the-same-closure-twice-registers-it-twice)): replacing, merging and evaluating closure entries all work, and only that identity check is missing.

## Semantics

### A default image beats the placeholder

The blank branch renders `<p class="sy-ta-placeholder">` and nothing else, but only when there is genuinely nothing to draw.
`defaultImageUrl()` is applied *before* the emptiness test, in [`getStateItems()`](../../../packages/tables/src/Columns/ImageColumn.php): a blank state with a default image resolves to one item carrying that URL, so the cell renders that image and the placeholder never appears.
Filament's order is the same (`$state = [null]` before the `blank($state)` check), so the two settings are alternatives rather than a fallback chain, and setting both makes the placeholder dead configuration.

### `getStateItems()` resolves URLs in PHP, not in the template

Filament's `toEmbeddedHtml()` wraps, default-substitutes and slices the state inline, then resolves each item's `src` inside a `$formatState` closure the markup calls.
The port puts all of that behind two getters so the partial stays a loop: `getStateItems()` answers one `{url, alt, state}` entry per value, the blank-state-with-default `[null]` substitution, the limit slice, the `getImageUrl() ?? defaultImageUrl` fallback and the per-item `alt` evaluation included, and `getStateOverLimitCount()` answers the `+N` count.
Both are Sylament-only members with rows in [translations.md's no-Filament-source table](../translations.md#names-with-no-filament-source); the rendered-dimension fallback chain (`imageHeight ?? (stacked ? 2rem : 2.5rem)`, width defaulting to the height for a circular or square column) lives in `getExtraTemplateData()`, the house home for exactly that kind of chain.

### Turning the existence check off does not stop the visibility read

`checkFileExistence(false)` skips only the `fileExists()` probe.
With `visibility()` unset, [the registry's minting](../support/filesystem-registry.md#getfileurl-mints-the-browser-facing-url) still reads each file's own visibility from the storage, one metadata call per image, and reads a visibility it cannot retrieve as private, which gets a temporary URL where the storage can sign one and no URL otherwise.
A column over a storage where per-file calls are expensive (S3) wants both knobs: `checkFileExistence(false)` **and** an explicit `visibility('public')`, which together touch the storage never.

### `square()` emits no class

Filament's `fi-circular` has a stylesheet rule (`rounded-full`); `fi-square` does not exist, and `isSquare()`'s only effect is width defaulting to the height.
The port mirrors that: `sy-circular` is a class, squareness is geometry.

### Extra img attribute registrations merge defaults-first

`extraImgAttributes(['decoding' => 'async'], merge: true)` after `extraImgAttributes(['loading' => 'lazy'])` yields `['decoding' => 'async', 'loading' => 'lazy']`: the later registration lands *first* and an earlier key wins a collision, because each loop step merges the accumulated bag *into* the new array as Laravel-style defaults.
Filament's own loop ([`getExtraImgAttributes()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php)) produces the identical order, as does support's [`HasExtraAttributes`](../support/has-extra-attributes.md#an-earlier-entry-wins-a-colliding-attribute-and-a-later-entrys-class-tokens-come-first).

## Divergences

### The disk comes from the registry, and a `new`-built column throws for one

Filament's `getDiskName()` falls back to `config('filament.default_filesystem_disk')` and carries a `'public'`-default-with-`'private'`-visibility → `'local'` swap that only means something against Laravel's disk conventions.
The port asks [`FilesystemRegistry`](../support/filesystem-registry.md) for the default and ships no name swap, the same choice forms' [`BaseFileUpload`](../forms/file-upload.md#disk-names-a-flysystem-storage-and-there-is-no-publiclocal-swap) makes.
The registry arrives by `#[Required]` setter, so a column built with `new` rather than through the container has none: the URL-state arms still work, because nothing touches a disk, and the first *path* state throws a `LogicException` naming the column and the fix.
`getDisk()` memoises per resolved name, not per instance, because `disk()` may name a closure.

### URL minting is the registry's, and visibility defers to the storage

The path arm of `getImageUrl()` is the existence check (unless turned off), then [`FilesystemRegistry::getFileUrl()`](../support/filesystem-registry.md#getfileurl-mints-the-browser-facing-url), where an unset visibility is read from the storage per file, an unreadable one is read as private, and a private file whose storage cannot mint a temporary URL gets `null` rather than a public link.
Those semantics are [forms' divergence](../forms/file-upload.md#visibility-defers-to-the-storage-and-a-private-file-never-falls-through-to-a-public-url) hoisted to the seam both packages share, replacing Filament's disk-name inference (`getVisibility()`: a `'public'` disk means public, anything else private) with a per-file truth.
`getVisibility()` therefore answers only the configured value, which is what Filament's `getCustomVisibility()` answers, so that getter has nothing left to distinguish and is not ported.
What this column does *not* inherit from Filament: the `now()->endOfHour()` rounding on the expiry, since the registry mints a plain `+N minutes`, and the `Storage::disk()` reach.

### Every emitted URL passes `sanitize_url()`, the passthrough included

Filament returns a URL-shaped state as-is.
Here the passthrough arm, a state `filter_var()` validates or a `data:` prefix, and `getDefaultImageUrl()` both run through [`sanitize_url()`](../support/helpers.md) with `data` added to the allowed schemes (an `<img src>` sink renders a data URI inert, and Filament explicitly supports them), and the registry sanitizes what the storage mints.
So a `javascript:` default image is `null`, and an absolute URL hiding a percent-encoded control character is refused where Filament would emit it escaped-but-live.
A `javascript:` state takes the passthrough arm only in a form `filter_var()` accepts, such as `javascript://host/…`, which the sanitizer refuses; a bare `javascript:alert(1)` fails `filter_var()` and falls to the disk arm as a path, exactly as in Filament.

### A zero or negative limit is unlimited

Filament computes `$limit && ($stateCount > $limit)`, so `limit(0)` is falsy and slices nothing, and the port's `$limit < 1` gives the same answer.
A negative limit is truthy there, though: Filament counts `$stateCount - $limit` images over the limit and `array_slice($state, 0, $limit)` drops images off the end, so `limit(-1)` over three images draws two and a `+4` chip.
The port treats every limit below one as no limit, so every image renders, because a chip counting more images than the cell holds reads as an accident of PHP truthiness rather than a behaviour to keep.

### A non-string state value falls to the default image

`getStateItems()` hands `getImageUrl(?string)` only a filled string; any other value, such as an integer, an entity or an array entry, resolves to the default image, the same picture the blank state gets, because the port's strict types refuse the coercion Filament relies on.
Filament's `$formatState` closure passes any filled value straight to `getImageUrl(?string)` in a file without strict types, so a scalar or a `Stringable` is coerced into a storage path, and any other object or an array throws a `TypeError` inside the render.

### The `sy-size-base` CSS arm is not ported

Filament's stylesheet answers `fi-size-base` beside `fi-size-md`, but its own class emitter turns a `TextSize` case into `fi-size-<value>` and passes any custom string through *bare*, and `TextSize` has no `base` case, so `'base'` renders as the class `base`, which `.fi-size-base` never matches.
The port's `getLimitedRemainingTextSizeClass()` behaves identically (`sy-size-` prefix for enum cases, custom strings as-is), so the dead arm is dropped from [`image-column.css`](../../../packages/tables/assets/src/styles/components/image-column.css): `xs`, `md` and `lg` have rules, and `sm` is the element's base `text-sm`.
