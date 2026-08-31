# ImageColumn (tables)

[`Sylament\Tables\Columns\ImageColumn`](../../../packages/tables/src/Columns/ImageColumn.php)
— a [`Column`](column.md) that renders its cell state as one or more `<img>`
elements: a plain URL or `data:` URI as-is, a storage path through
[`FilesystemRegistry`](../support/filesystem-registry.md)'s URL minting. It
ports Filament's
[`Filament\Tables\Columns\ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php),
pulled forward out of the [M4 · 4 column catalog](../roadmap.md#m4--tables-widening)
as [pulled-forward 25](../roadmap.md#pulled-forward). Everything
[`Column`](column.md) carries — the name, the label, the record binding, the
state read, the closure injections — is tracked there, not repeated here.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses). The blocked rows below wait on
`Column`-level concerns the M4 · 4 remainder owns (tooltip, url, placeholder,
`inline()`, the wrapper `extraAttributes()`), not on anything of this class's
own.

Reference — the Filament source this is diffed against:

- [`tables/src/Columns/ImageColumn.php`](../../../vendor/filament/tables/src/Columns/ImageColumn.php)
  — the class and its `toEmbeddedHtml()`
- [`tables/resources/css/columns/image.css`](../../../vendor/filament/tables/resources/css/columns/image.css)
  — the stylesheet [`image-column.css`](../../../packages/tables/assets/src/styles/components/image-column.css)
  is the prefix-renamed port of

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in Details,
deleted once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences); **semantics** → the permanent note in
[Semantics](#semantics). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`disk()` / `getDiskName()` / `getDisk()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::disk()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#the-disk-comes-from-the-registry-and-a-new-built-column-throws-for-one) |
| [`visibility()` / `getVisibility()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::visibility()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#url-minting-is-the-registrys-and-visibility-defers-to-the-storage) |
| [`getImageUrl()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::getImageUrl()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#url-minting-is-the-registrys-and-visibility-defers-to-the-storage) · [diverges](#every-emitted-url-passes-sanitize_url-the-passthrough-included) |
| [`checkFileExistence()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::checkFileExistence()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#turning-the-existence-check-off-does-not-stop-the-visibility-read) |
| [`imageSize()` / `imageHeight()` / `imageWidth()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | — |
| [`circular()` / `square()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#square-emits-no-class) |
| [`alt()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::alt()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | — |
| [`defaultImageUrl()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::defaultImageUrl()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#every-emitted-url-passes-sanitize_url-the-passthrough-included) |
| [`stacked()` / `overlap()` / `ring()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | — |
| [`limit()` / `limitedRemainingText()` / `limitedRemainingTextSize()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#a-zero-or-negative-limit-is-unlimited) |
| [`extraImgAttributes()`](../../../packages/tables/src/Columns/ImageColumn.php) | [`ImageColumn::extraImgAttributes()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [semantics](#extra-img-attribute-registrations-merge-defaults-first) |
| [`wrap()`](../../../packages/support/src/Components/Concerns/CanWrap.php) | [`CanWrap`](../../../vendor/filament/support/src/Concerns/CanWrap.php) | done | — | — |
| [`getStateItems()` / `getStateOverLimitCount()` — the cell's image list](../../../packages/tables/src/Columns/ImageColumn.php) | — (Sylament-only) | done | — | [semantics](#getstateitems-resolves-urls-in-php-not-in-the-template) |
| [`$template`](../../../packages/tables/src/Columns/ImageColumn.php) + [the partial it names](../../../packages/tables/templates/partials/ImageColumn/index.html.twig) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | done | — | [diverges](#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view) |
| [`getLimitedRemainingTextSizeClass()`](../../../packages/tables/src/Columns/ImageColumn.php) | — (Sylament-only) | done | — | [semantics](#the-sy-size-base-css-arm-is-not-ported) |
| `size()` / `height()` / `width()` / `rounded()` — deprecated aliases | [`ImageColumn`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | skipped | — | The port ships no deprecated Filament surface — [renames.md](../renames.md) |
| The tooltip arms (`getTooltip($stateItem)`, `getEmptyTooltip()`) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | todo | `tooltip()` on [`Column`](column.md) (M4 · 4) | — |
| The link arm (`getUrl($stateItem)` wrapping each image in an `<a>`) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | todo | `url()` on [`Column`](column.md) (M4 · 4) | — |
| The blank-state placeholder (`getPlaceholder()`) | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | todo | `placeholder()` on [`Column`](column.md) (M4 · 4) | — |
| `fi-inline` on the root | [`ImageColumn::toEmbeddedHtml()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php) | todo | `inline()` on [`Column`](column.md) (M4 · 4) | — |
| The wrapper `extraAttributes()` on the root div | [`Column::getExtraAttributeBag()`](../../../vendor/filament/tables/src/Columns/Column.php) | todo | `extraAttributes()` on [`Column`](column.md) (M4 · 4) | — |

The surface rows are covered by the Unit
[`ImageColumnTest`](../../../packages/tables/tests/Unit/Columns/ImageColumnTest.php)
— the defaults, the boolean and dimension evaluation (`40` → `'40px'`, a string
through untouched, `imageSize()` setting both), the rendered-dimension fallback
chain through `getExtraTemplateData()` (stacked `2rem`, plain `2.5rem`,
circular/square deriving width from height), the state-injected `alt` closure,
the passthrough / refusal / disk-path-throws / null arms of `getImageUrl()`, the
item list with its limit slicing, blank-state and default-image arms, the
remaining-text size mirror of `TextColumn`'s handling, and the extra-img
merge-vs-replace behaviour. The `$template` row is covered by the Integration
[`ImageColumnTest`](../../../packages/tables/tests/Integration/Columns/ImageColumnTest.php),
which renders columns inside a real table through the same
`td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-image` chain the text column's
suite reads, in two halves: the URL arm (one `img` per value with the default
height, the circular/stacked/ring/overlap/wrap/alignment classes, the `+N` chip
with its size class, the blank and default-image states, the extra img
attributes landing on each element) and the disk arm, over two real local
flysystem storages the fixture kernel now registers exactly as
[forms' kernel](../../../packages/forms/tests/Fixtures/Kernel.php) does — the
public-URL mint, the missing-file drop, the no-storage-touch fast path, and the
private file that gets no URL at all. The registry's own minting semantics are
pinned one package down, in support's
[`FilesystemRegistryTest`](../../../packages/support/tests/Unit/Filesystem/FilesystemRegistryTest.php).

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### `getStateItems()` resolves URLs in PHP, not in the template

Filament's `toEmbeddedHtml()` wraps, default-substitutes and slices the state
inline, then resolves each item's `src` inside a `$formatState` closure the
markup calls. The port puts all of that behind two getters so the partial stays
a loop: `getStateItems()` answers one `{url, alt}` pair per value — the
blank-state-with-default `[null]` substitution, the limit slice, the
`getImageUrl() ?? defaultImageUrl` fallback and the per-item `alt` evaluation
included — and `getStateOverLimitCount()` answers the `+N` count. Both are
Sylament-only members with rows in
[renames.md's no-Filament-source table](../renames.md#names-with-no-filament-source);
the rendered-dimension fallback chain (`imageHeight ?? (stacked ? 2rem : 2.5rem)`,
width defaulting to the height for a circular or square column) lives in
`getExtraTemplateData()`, the house home for exactly that kind of chain.

One narrowing rides on it: a **non-string** state value (an entity, an array
entry that is not a path) falls to the default image rather than reaching
`getImageUrl(?string)`. Filament's untyped closure would `TypeError` inside the
view; here the guard is explicit and the outcome is the same picture the blank
state gets.

### Turning the existence check off does not stop the visibility read

`checkFileExistence(false)` skips only the `fileExists()` probe. With
`visibility()` unset, [the registry's minting](../support/filesystem-registry.md#getfileurl-mints-the-browser-facing-url)
still reads each file's own visibility from the storage — one metadata call per
image — and reads a *missing* file as private, which is no URL at all. A column
over a storage where per-file calls are expensive (S3) wants both knobs:
`checkFileExistence(false)` **and** an explicit `visibility('public')`, which
together touch the storage never. Pinned by the Integration case named for it.

### `square()` emits no class

Filament's `fi-circular` has a stylesheet rule (`rounded-full`);
`fi-square` does not exist — `isSquare()`'s only effect is width defaulting to
the height. The port mirrors that: `sy-circular` is a class,
squareness is geometry.

### A zero or negative limit is unlimited

Filament computes `$limit && ($stateCount > $limit)`, so `limit(0)` is falsy
and slices nothing. The port spells the same outcome as `$limit < 1` returning
zero over-limit — every image renders.

### Extra img attribute registrations merge defaults-first

`extraImgAttributes(['decoding' => 'async'], merge: true)` after
`extraImgAttributes(['loading' => 'lazy'])` yields
`['decoding' => 'async', 'loading' => 'lazy']` — the later registration lands
*first* and an earlier key wins a collision, because each loop step merges the
accumulated bag *into* the new array as Laravel-style defaults. Filament's own
loop ([`getExtraImgAttributes()`](../../../vendor/filament/tables/src/Columns/ImageColumn.php))
produces the identical order, as does support's
[`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php);
pinned so the order is a documented fact rather than a surprise.

### The `sy-size-base` CSS arm is not ported

Filament's stylesheet answers `fi-size-base` beside `fi-size-md`, but its own
class emitter turns a `TextSize` case into `fi-size-<value>` and passes any
custom string through *bare* — so `'base'` renders as the class `base`, which
`.fi-size-base` never matches. The port's
`getLimitedRemainingTextSizeClass()` behaves identically (`sy-size-` prefix for
enum cases, custom strings as-is), so the dead arm is dropped from
[`image-column.css`](../../../packages/tables/assets/src/styles/components/image-column.css):
`xs`, `md` and `lg` have rules, `sm` is the element's base `text-sm`.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The cell renders a Twig partial where Filament emits an embedded view

The same translation every column makes — see
[text-column.md's note](text-column.md#the-cell-renders-a-twig-partial-where-filament-emits-an-embedded-view),
which carries the reasoning once. Two consequences specific to this template:
the `src` attribute renders `?? ''` rather than vanishing when both the item's
URL and the default image are null (an absent attribute is not the same as an
empty one to idiomorph — the `TextInput` `value` rule), and a blank state with
no default image renders the empty root `div` alone, since the placeholder arm
is a blocked row.

### The disk comes from the registry, and a `new`-built column throws for one

Filament's `getDiskName()` falls back to `config('filament.default_filesystem_disk')`
and carries a `'public'`-default-with-`'private'`-visibility → `'local'` swap
that only means something against Laravel's disk conventions. The port asks
[`FilesystemRegistry`](../support/filesystem-registry.md) for the default and
ships no name swap — the same call forms'
[`BaseFileUpload`](../forms/file-upload.md#disk-names-a-flysystem-storage-and-there-is-no-publiclocal-swap)
made. The registry arrives by `#[Required]` setter, so a column built with
`new` rather than through the container has none: the URL-state arms still work
(nothing touches a disk), and the first *path* state throws a `LogicException`
naming the column and the fix. `getDisk()` memoises per resolved name, not per
instance, since `disk()` may name a closure.

### URL minting is the registry's, and visibility defers to the storage

The path arm of `getImageUrl()` is: existence check (unless turned off), then
[`FilesystemRegistry::getFileUrl()`](../support/filesystem-registry.md#getfileurl-mints-the-browser-facing-url)
— where an unset visibility is read from the storage per file, an unreadable
one is read as private, and a private file whose storage cannot mint a
temporary URL gets `null` rather than a public link. Those semantics are
[forms' divergence](../forms/file-upload.md#visibility-defers-to-the-storage-and-a-private-file-never-falls-through-to-a-public-url)
hoisted to the seam both packages share, replacing Filament's disk-name
inference (`getVisibility()`: `'public'` disk means public, anything else
private) with a per-file truth. What this column does *not* inherit from
Filament: the `now()->endOfHour()` rounding on the expiry — the registry mints
a plain `+N minutes` — and the `Storage::disk()` reach.

### Every emitted URL passes `sanitize_url()`, the passthrough included

Filament returns a URL-shaped state as-is. Here the passthrough arm — a state
`filter_var()` validates or a `data:` prefix — and `getDefaultImageUrl()` both
run through [`sanitize_url()`](../support/helpers.md) with `data` added to the
allowed schemes (an `<img src>` sink renders a data URI inert, and Filament
explicitly supports them), and the registry sanitizes what the storage mints.
So a `javascript:` default image is `null`, and a control-character-obfuscated
absolute URL is refused where Filament would emit it escaped-but-live. A
`javascript:` *state* never reaches the sanitizer at all — `filter_var()`
rejects it, so it falls to the disk arm as a path, exactly as in Filament.
