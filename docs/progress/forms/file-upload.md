# File upload

One file for both halves of the vertical: the abstract
[`Sylament\Forms\Components\BaseFileUpload`](../../../packages/forms/src/Components/BaseFileUpload.php),
which carries the whole upload lifecycle, and the shipped field
[`Sylament\Forms\Components\FileUpload`](../../../packages/forms/src/Components/FileUpload.php),
which adds only what FilePond needs. They are one row each in the
[README](README.md) and one page here because every divergence below is a
property of the lifecycle rather than of the control: Filament's own split puts
the storage, validation and state work on the base and the render on the
subclass, and so does ours.

Two neighbours own the pieces this page leans on. The bytes live on
[`TemporaryUploads`](temporary-uploads.md) between the upload and the save, and
the token in raw state is what stands in for them; the minimum-size constraint
Symfony has no counterpart for is [`MinFileSize`](can-be-validated.md), filed
with the other first-party constraints. The storage seam itself is support's
[`FilesystemRegistry`](../support/filesystem-registry.md), and the browser
reaches the field through schemas'
[`uploadSchemaComponentFile()`](../schemas/interacts-with-schemas.md).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).
Everything the scoped FilePond bundle and the editor serve is done; the
deprecated aliases are **skipped**, as the port ships no deprecated surface.

Reference — the Filament source this is diffed against:

- [`forms/src/Components/BaseFileUpload.php`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php)
- [`forms/src/Components/FileUpload.php`](../../../vendor/filament/forms/src/Components/FileUpload.php)
- [`schemas/src/Components/StateCasts/FileUploadStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/FileUploadStateCast.php)
  — ported into schemas, tracked there

## Surface — `BaseFileUpload`

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics) or [Divergences](#divergences), or `—`. A row whose
_Ports_ cell is `—` has no Filament counterpart.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`acceptUploadedFile(string $fileKey, UploadedFile $file): ?array`](../../../packages/forms/src/Components/BaseFileUpload.php) | — (Livewire's `_startUpload`/`_finishUpload` pair) | done | — | [diverges](#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| [`hydrateFiles()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`hydrateFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#hydratefiles-is-not-a-security-boundary) |
| [`saveUploadedFiles()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`saveUploadedFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#two-filament-defects-in-the-store-pass-are-not-ported) |
| [`saveUploadedFile(TemporaryUploadedFile $file): ?string`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`saveUploadedFile()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-store-step-moves-on-one-storage-and-streams-across-two) |
| [`getUploadedFiles(): ?array`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`getUploadedFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#the-payload-is-rendered-not-fetched-so-getuploadedfiles-is-not-browser-callable) |
| [`getUploadedFile(string $file, string\|array\|null $storedFileNames): ?array`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`getUploadedFile()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`getUploadedFileUrl(string $file): ?string`](../../../packages/forms/src/Components/BaseFileUpload.php) | inline in [`getUploadedFile()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#visibility-defers-to-the-storage-and-a-private-file-never-falls-through-to-a-public-url) |
| [`removeUploadedFile(string $fileKey)`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`removeUploadedFile()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#the-tampering-gate-excludes-our-own-tokens) |
| [`deleteUploadedFile(string $fileKey): void`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`deleteUploadedFile()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#the-three-exposed-mutators-return-what-the-seam-can-serialise) |
| [`reorderUploadedFiles(array $fileKeys): void`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`reorderUploadedFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#two-filament-defects-in-the-store-pass-are-not-ported) |
| [`getUploadedFileConstraints(): list<Constraint>`](../../../packages/forms/src/Components/BaseFileUpload.php) | part of [`getValidationRules()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| [`buildValidationConstraints(): list<Constraint>`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`getValidationRules()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| `ARRAY_VALIDATION_RULES` / `isArrayValidationRule()` | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | skipped | — | [diverges](#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| `dehydrateValidationRules()` | [`dehydrateValidationRules()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | skipped | — | [diverges](#the-filename-maps-path-needs-no-nullable-entry) |
| [`preventFilePathTampering()` / `shouldPreventFilePathTampering()` / `isFilePathAuthorized()` / `getOriginalFilePaths()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#the-tampering-gate-reads-the-records-current-value) |
| [`acceptedFileTypes()` / `getAcceptedFileTypes()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`acceptedFileTypes()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#the-arrayable-arm-of-acceptedfiletypes-becomes-iterable) |
| [`maxSize()` / `minSize()` / `getMaxSize()` / `getMinSize()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`maxSize()` / `minSize()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| [`maxFiles()` / `minFiles()` / `getMaxFiles()` / `getMinFiles()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`maxFiles()` / `minFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-ceiling-on-browser-driven-writes) |
| [`maxParallelUploads()` / `getMaxParallelUploads()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`maxParallelUploads()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`multiple()` / `isMultiple()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`multiple()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`deletable()` / `downloadable()` / `openable()` / `reorderable()` / `pasteable()` / `previewable()` and their `is*()` getters](../../../packages/forms/src/Components/BaseFileUpload.php) | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`directory()` / `getDirectory()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`directory()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`disk()` / `getDiskName()` / `getDisk()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`disk()` / `getDiskName()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#disk-names-a-flysystem-storage-and-there-is-no-publiclocal-swap) |
| [`visibility()` / `getVisibility()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`visibility()` / `getVisibility()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#visibility-defers-to-the-storage-and-a-private-file-never-falls-through-to-a-public-url) |
| `getCustomVisibility()` | [`getCustomVisibility()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | skipped | — | [diverges](#visibility-defers-to-the-storage-and-a-private-file-never-falls-through-to-a-public-url) |
| [`preserveFilenames()` / `shouldPreserveFilenames()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`preserveFilenames()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`moveFiles()` / `shouldMoveFiles()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`moveFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-store-step-moves-on-one-storage-and-streams-across-two) |
| [`storeFiles()` / `shouldStoreFiles()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`storeFiles()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`fetchFileInformation()` / `shouldFetchFileInformation()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`fetchFileInformation()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`storeFileNamesIn()` / `getFileNamesStatePath()` / `storeFileName()` / `removeStoredFileName()` / `getStoredFileNames()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#the-filename-map-keeps-filaments-resolved-vs-raw-asymmetry) |
| [`getStateToDehydrate()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`getStateToDehydrate()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`getDefaultStateCasts()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`getDefaultStateCasts()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [schemas](../schemas/README.md) |
| [`imageAspectRatio()` / `getImageAspectRatio()` / `calculateAspectRatio()` / `normalizeAspectRatio()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`imageAspectRatio()`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [semantics](#imageaspectratio-validates-and-automaticallycropimagestoaspectratio-crops) |
| [The six `*Using` setters](../../../packages/forms/src/Components/BaseFileUpload.php) — `deleteUploadedFileUsing()`, `getUploadedFileUsing()`, `getOpenableFileUrlUsing()`, `getDownloadableFileUrlUsing()`, `reorderUploadedFilesUsing()`, `saveUploadedFileUsing()` | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | — |
| [`getUploadedFileNameForStorageUsing()` / `getUploadedFileNameForStorage()`](../../../packages/forms/src/Components/BaseFileUpload.php) | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | done | — | [diverges](#a-generator-that-names-nothing-throws) |
| [`getMaxUploadSize(): int`](../../../packages/forms/src/Components/BaseFileUpload.php) | — | done | — | [diverges](#the-client-side-size-ceiling-is-the-minimum-of-the-fields-and-phps) |
| [`setTemporaryUploads()` / `setFilesystemRegistry()` / `setValidator()` and their getters](../../../packages/forms/src/Components/BaseFileUpload.php) | — (Filament reaches `Storage::` and `Validator::` facades) | done | — | [diverges](#the-three-services-arrive-by-required-setter-not-by-facade) |
| `uploadingMessage()` / `HasUploadingMessage` | [`Concerns/HasUploadingMessage.php`](../../../vendor/filament/forms/src/Components/Concerns/HasUploadingMessage.php) | blocked | a form-processing channel | [diverges](#uploadingmessage-has-no-render-site) |
| `HasNestedRecursiveValidationRules` | [`Concerns/HasNestedRecursiveValidationRules.php`](../../../vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php) | todo | — | [diverges](#per-file-constraints-run-at-upload-time-collection-constraints-at-save) |
| `RestrictsFileUploadsToSchemaComponents` | [`schemas/…/RestrictsFileUploadsToSchemaComponents.php`](../../../vendor/filament/schemas/src/Concerns/RestrictsFileUploadsToSchemaComponents.php) | skipped | — | [diverges](#the-upload-action-is-closed-by-construction-so-there-is-nothing-to-restrict) |
| `enableDownload()` / `enableOpen()` / `enableReordering()` / `disablePreview()` / `moveFile()` / `storeFile()` | [`BaseFileUpload`](../../../vendor/filament/forms/src/Components/BaseFileUpload.php) | skipped | — | the port ships no deprecated surface |

## Surface — `FileUpload`

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [The partial](../../../packages/forms/templates/partials/FileUpload/index.html.twig) and its 51 Stimulus values | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | [semantics](#the-dom-contract-is-the-controllers-only-input) |
| [`getExtraTemplateData()`](../../../packages/forms/src/Components/FileUpload.php) | the `toEmbeddedHtml()` preamble | done | — | [diverges](#the-three-maps-are-object-cast-so-an-empty-one-serialises-as-) |
| [`avatar()` / `isAvatar()`](../../../packages/forms/src/Components/FileUpload.php) | [`avatar()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`image()`](../../../packages/forms/src/Components/FileUpload.php) | [`image()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`appendFiles()` / `shouldAppendFiles()`](../../../packages/forms/src/Components/FileUpload.php) | [`appendFiles()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`imagePreviewHeight()` / `getImagePreviewHeight()`](../../../packages/forms/src/Components/FileUpload.php) | [`imagePreviewHeight()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`itemPanelAspectRatio()` / `getItemPanelAspectRatio()`](../../../packages/forms/src/Components/FileUpload.php) | [`itemPanelAspectRatio()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`panelAspectRatio()` / `getPanelAspectRatio()`](../../../packages/forms/src/Components/FileUpload.php) | [`panelAspectRatio()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`panelLayout()` / `getPanelLayout()`](../../../packages/forms/src/Components/FileUpload.php) | [`panelLayout()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`loadingIndicatorPosition()` / `removeUploadedFileButtonPosition()` / `uploadButtonPosition()` / `uploadProgressIndicatorPosition()` and their getters](../../../packages/forms/src/Components/FileUpload.php) | [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| [`mimeTypeMap()` / `getMimeTypeMap()`](../../../packages/forms/src/Components/FileUpload.php) | [`mimeTypeMap()`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | [diverges](#the-bundle-drops-fileponds-mime-database) |
| `automaticallyCropImagesToAspectRatio()` and the whole resize family (`automaticallyResizeImagesMode()`, `automaticallyResizeImagesToHeight()`, `automaticallyResizeImagesToWidth()`, `automaticallyUpscaleImagesWhenResizing()`) | [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | [diverges](#getimageeditoractions-carries-methods-and-params-not-alpine) |
| `orientImagesFromExif()` / `shouldOrientImagesFromExif()` | [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | — |
| The image-editor family — `imageEditor()`, `circleCropper()`, `editableSvgs()`, `confirmSvgEditing()`, `automaticallyOpenImageEditorForAspectRatio()`, `imageEditorViewportWidth()` / `Height()`, `imageEditorMode()`, `imageEditorEmptyFillColor()`, `imageEditorAspectRatioOptions()`, `getImageEditorActions()` | [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php) | done | — | [diverges](#getimageeditoractions-carries-methods-and-params-not-alpine) |
| `idleLabel()` / `imageCropAspectRatio()` / `imageResizeMode()` / `imageResizeTargetHeight()` / `imageResizeTargetWidth()` / `imageResizeUpscale()` / `orientImageFromExif()` / `imageEditorAspectRatios()` and their deprecated getters | [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php) | skipped | — | the port ships no deprecated surface |
| `$publishedViewOverrideCheckPath` | [`FileUpload`](../../../vendor/filament/forms/src/Components/FileUpload.php) | skipped | — | there is no published-view mechanism to probe for |

Coverage:
[`Unit/Components/BaseFileUploadTest`](../../../packages/forms/tests/Unit/Components/BaseFileUploadTest.php)
— the constraint split read through the public getter (one `NotBlank`, never
two), the aspect-ratio constraints, the three record arms of
`getOriginalFilePaths()`, the disk fallback, both filename arms, the nullable
visibility, the state cast, the PHP ceiling, and the hydration filter;
[`Unit/Components/FileUploadTest`](../../../packages/forms/tests/Unit/Components/FileUploadTest.php)
— Filament's twelve-call `avatar()`, the editor family's defaults,
derivations and throws, the aspect-ratio option map, the thirteen toolbar
actions, the transform flag and the FilePond-facing getters;
[`Integration/Components/FileUploadTest`](../../../packages/forms/tests/Integration/Components/FileUploadTest.php)
— the DOM contract, both `{}` maps, the single extra-attribute spread, the
`data-live-ignore` pond, and the public/absent URL arms;
[`Integration/LiveFileUploadTest`](../../../packages/forms/tests/Integration/LiveFileUploadTest.php)
— the round trip: accept, refuse (type, over, under), save, reorder, rename,
move vs stream, visibility, remove, delete, disabled, the tampering gate on both
faces, and the state hook.

## Semantics

### The ceiling on browser-driven writes

This is the toolkit's first browser-writable file endpoint, so the ceiling is
worth stating rather than leaving a reader to infer. A client may add **one
temporary file per `fileKey` it mints**, and it mints those itself. Each file is
bounded by the field's own `acceptedFileTypes()` and `maxSize()`, which run
before anything is written, and by PHP's `upload_max_filesize` /
`post_max_size`. In **aggregate** the only bound is the 24-hour sweep
[`TemporaryUploads::prune()`](temporary-uploads.md#the-sweep-rides-on-upload-traffic-and-races-itself-on-purpose)
performs on every successful upload: `maxFiles()` is a save-time `Count`, not an
upload-time gate, so a client that never submits the form can hold as many
temporary files as it is willing to upload. That is Filament parity — the same
is true there — and it is why a storage quota, not `maxFiles()`, is what bounds
the disk.

### `hydrateFiles()` is not a security boundary

It runs from `afterStateHydrated`, so it only sees state that came off the
record at form load; a path a live request later writes into the bag never
passes through it. Its job is to drop blanks and paths the storage no longer
holds (`fetchFileInformation(false)` skips the round trip), so a deleted file
does not render as a broken item. What authorises a *submitted* path is
[`preventFilePathTampering()`](#the-tampering-gate-reads-the-records-current-value),
and nothing else. Filament carries the same warning at the same place.

### The store step moves on one storage and streams across two

`saveUploadedFile()` moves the bytes only when `moveFiles()` is on **and** the
field's storage is the same one the temporary store writes to — `move()` is a
single-storage operation, so the names have to match. Otherwise it opens a read
stream on the temporary file and `writeStream()`s it onto the field's storage.
Either way the temporary file and its sidecar are deleted; both deletes are
idempotent, because `saveUploadedFiles()` deletes as well (Filament's placement,
kept, so a custom `saveUploadedFileUsing` cannot leak the temporary file by
forgetting to). A storage failure is **not** swallowed: `UnableToWriteFile` and
`UnableToMoveFile` propagate, because a file the user was told was saved must
not vanish quietly.

### The filename map keeps Filament's resolved-vs-raw asymmetry

`storeFileName()`, `removeStoredFileName()` and `getStoredFileNames()` address
the sibling path through `makeGetUtility()` / `makeSetUtility()`, which resolve
a relative path themselves, while `getFileNamesStatePath()` resolves it to the
absolute path the dehydrated bag is keyed by. That is Filament's shape and it is
kept: the utilities need the relative form and the dehydration needs the
absolute one. Ours evaluates the declaration in both places, where Filament
reads the raw property in three of the four.

### The DOM contract is the controller's only input

The partial renders the controller element with all 51 Stimulus values and a
`data-live-ignore` subtree for FilePond to build into. That split is the whole
contract: the element **morphs**, so every value is re-read after a live
update — which is how `uploadedFiles` and `state` stay the server's truth,
exactly as the [select's option map](select.md) does — while the pond subtree
never does, because FilePond owns its DOM after `create()`. A `disabled` flip
therefore reaches the pond through its value and `setOptions()`, never through
the `<input>`, whose attributes stop updating the moment FilePond adopts it.

The editor dialog is a second `data-live-ignore` island beside the pond
container, so its chrome — the aspect-ratio buttons, the group titles, the
footer — is first-render only and never reacts to a later live update.

### `imageAspectRatio()` validates and `automaticallyCropImagesToAspectRatio()` crops

Filament splits the ratio in two, and so do we. `imageAspectRatio()` sets the
value and reaches the browser through **no** Stimulus value of its own — it is a
server-side `Image` constraint at upload time and nothing else. The ratio
FilePond crops to is `getAutomaticallyCropImagesAspectRatio()`'s, which reads
the same value but returns `null` unless `automaticallyCropImagesToAspectRatio()`
turned the flag on, and takes the first entry when the value is a list. The
deprecated `imageCropAspectRatio()` did both at once, so a migrating call
becomes two: `imageAspectRatio($ratio)` **and**
`automaticallyCropImagesToAspectRatio()`.

### The auto-open tolerance and the validator's rounding

Two rounding regimes meet on the same ratio, and the window between them is
Filament's as much as ours. The client skips the auto-open editor when the
picked image is within `0.01` of the target ratio, while the server's `Image`
constraint compares `round(w / h, 2)` against bounds Symfony also rounds to two
decimals — so a 1.009 image passes the client and still fails `minRatio: 1.0`.
The window closes whenever `automaticallyCropImagesToAspectRatio()` is on as
well, because the transform then crops to the ratio before the bytes are sent.

An aspect-ratio rule also buys a MIME rule: the `Image` constraint carries
Symfony's `'image/*'` default set, so declaring a ratio enforces "sniffs as an
image" too — true since the constraint landed, written down here.

### The editor re-adds the edited file as a new upload

The image-edit plugin is bundled for its chrome, not for its contract. It
supplies the per-item edit button and the `EDIT_ITEM` route, and the
`imageEditEditor` bridge hangs off that route — `open` calls the controller's
`#loadEditor()`, `onconfirm` is a **no-op** (Filament's, verbatim), and
`oncancel`/`onclose` both close the dialog. The confirm-and-metadata contract a
commercial editor would answer with `markup`/`filter`/`colors` metadata goes
unused here exactly as it does in Filament.

Saving does the work instead. Cropper's `getCroppedCanvas()` output — masked on a
second canvas under `circleCropper()` — becomes a blob, the original pond item is
removed with `removeFile(id, { revert: true })`, and a new `File` named
`<name>-vN.<ext>` is `addFile()`d. The edited file therefore re-enters at the top
of the pipeline as an ordinary added file: validate, transform, `server.process`.
The schemas seam and the server need nothing new — an edit is just another
upload.

What reaches `server.process` after a transform is not a `File`, though. The
transform plugin resolves its output as a `Blob` carrying a `name` and a
`lastModified`, which FilePond types as `ActualFileObject` (`Blob & { name,
lastModified }`) rather than `File` — so `#process()` wraps it into a real
`File` before the seam, which attaches the upload through a `DataTransfer` and
accepts nothing else. A field with no crop or resize configured hands over the
browser's own `File` untouched, which is why M3·7's cast never failed and why
every transformed field threw until the wrap landed.

`removeFile(id, { revert: true })` has two arms (`filepond.esm.js:4879-4934`) and
both are already ours. A file uploaded in this session is a processed item, so
FilePond calls `server.revert` — our `removeUploadedFile`, which drops the
temporary file and its sidecar. A file loaded from the record is a `local` item,
so it calls `server.remove` — our `deleteUploadedFile`, which disowns the state
entry and leaves the stored file to the deleter closure, M3·7's semantics
unchanged. Editing a stored file is therefore a replace, not a copy.

The swap needs a guard, because the pond subtree is `data-live-ignore` and the
reconcile runs on every morph: `#editorSwapName` holds the new file's name from
just before the remove until the re-added item starts processing, and
`#reconcile()` returns early while it is set — so a morph landing between the
remove and the add cannot rebuild `pond.files` from a state that no longer
describes the pond. It is cleared at the top of `#process()`, on an `addFile()`
rejection, and by the `removefile` event when the re-added item is removed before
it ever uploaded.

One upstream override does nothing here. image-edit installs a
`SHOULD_REMOVE_ON_REVERT` filter, but FilePond 4.32.12's core has no site for it
(`REVERT_ITEM_PROCESSING` decides removal from `instantUpload` and `isMockItem`
alone), so the filter is inert and the ✕ button keeps M3·7's behaviour.

### EXIF orientation is the browser's on current engines

`orientImagesFromExif()` ports with Filament's `true` default, and on every
current browser it writes nothing at all. The exif-orientation plugin probes the
engine with a 2×1 JPEG carrying a 90° rotation tag and reads a file's orientation
only when the engine did **not** auto-orient that probe; since
`image-orientation: from-image` became the CSS default, no current engine fails
it, so no `exif` metadata is written. The transform plugin then sees
`orientation: null`, its `getBitmap()` short-circuits (`null <= 1` is true), and
it draws the already-oriented bitmap — the right result by a different route. The
plugin earns its keep only on a non-auto-orienting engine, where it is also what
feeds image-resize's dimension swap. So the default stays `true`: it costs
nothing today and costs correctness there.

### A strict CSP needs `worker-src blob:`

The transform plugin resizes on a Web Worker it builds at runtime from a
stringified function and a `Blob` URL, so an application serving a strict
Content-Security-Policy must allow `worker-src blob:` or the resize fails. The
worker path is taken only when a transform is actually configured — with no crop,
resize or filter the plugin goes straight to `toBlob()` — so a field that merely
uploads is unaffected. This is upstream's shape, not a Sylament choice, and it is
recorded beside the field's other platform requirements because the alternative
is not shipping the transform at all.

## Divergences

### Per-file constraints run at upload time, collection constraints at save

Filament stores first and validates afterwards, splitting rules by **name**
against a twelve-entry `ARRAY_VALIDATION_RULES` allow-list. We split by
**constraint class** and move the per-file half forward:

- `getUploadedFileConstraints()` — `File(mimeTypes:)` from
  `acceptedFileTypes()`, `File(maxSize: "{n}Ki")` from `maxSize()`,
  [`MinFileSize`](can-be-validated.md) from `minSize()`, an `Image` with both
  ratio bounds from `imageAspectRatio()` (several ratios become an
  `AtLeastOneOf`), plus every user constraint that is an `instanceof File`.
  `Image extends File`, so a user's own image constraint lands here too. Run by
  `acceptUploadedFile()` against the real `UploadedFile`, before a byte is
  written.
- `buildValidationConstraints()` — everything the parent contributes minus the
  `File` entries, plus `Count(min:, max:)` for `minFiles()`/`maxFiles()` and the
  tampering `Callback`. Run over the collection at save.

Two reasons, both forcing. Symfony's `File` and `Image` validators need a local
readable path, which a stored flysystem key is not, so they could not run at
save at all. And Filament's order orphans files on a failed submit and never
re-runs size or MIME rules on a retry. The signed token is what makes skipping
save-time per-file validation safe: a token is proof that *this* field validated
*this* file.

The split hangs off `buildValidationConstraints()`, never the public
`getValidationConstraints()` — which is
`applyValidationMessages(buildValidationConstraints())` and whose parent hook
already emits the required constraint. Overriding the getter would render
`NotBlank` twice and skip the message layer; six shipped fields override the
hook and none the getter. `getUploadedFileConstraints()` returns through
`applyValidationMessages()` too, so a field's own message overrides reach an
upload refusal.

`HasNestedRecursiveValidationRules` collapses into this split for this field and
stays **todo** for its other consumers. `ARRAY_VALIDATION_RULES` and
`isArrayValidationRule()` have nothing left to do.

### The tampering gate reads the record's current value

Filament reads Eloquent's `getOriginal()`. There is no Doctrine twin worth
reaching for, and none is needed: `Schema::getData()` calls `getState()` — and
therefore `validate()`, where the gate's `Callback` runs — *before* it writes
anything onto the record, so the record's **current** value is its original at
gate time. `getOriginalFilePaths()` has three arms, matching
`getRecord(): object|array|null`: an object reads through schemas'
`PropertyAccessor::getFromRecord()`, an **array record** is read by key (an
array record supplies state and cannot be mapped, so its seed values are its
originals), and anything else — no record, no such property, or an uninitialized
one raising `AccessException` — authorises **nothing**. The gate fails closed
exactly as Filament's does for a non-model.

It stays opt-in, with Filament's warning: without it, a stored path in the bag
is client-controlled and is passed through to the storage untouched.

### The tampering gate excludes our own tokens

Filament's predicate in `removeUploadedFile()` is a bare `is_string($file)`,
because its temporary entries are objects. Ours are token **strings**, and a
freshly minted token is never among the record's original paths — so the
unexcluded gate would refuse the user's own just-uploaded file: the pond would
revert the item and the temporary file would leak until the sweep. Both the
mutator and the save-time `Callback` therefore skip
`TemporaryUploads::isToken()` entries, which is safe because a token already
proves this field validated this file.

### The three exposed mutators return what the seam can serialise

`removeUploadedFile()`, `deleteUploadedFile()` and `reorderUploadedFiles()` are
`#[ExposedLiveComponentMethod]`. Our seam puts a method's result straight into a
browser-event payload, so `deleteUploadedFile()` returns `void` where Filament
returns `static` — a component object has no business travelling there.
`removeUploadedFile()` keeps its `string|TemporaryUploadedFile|null`, because
the value object is `JsonSerializable` and serialises to its token.

### The payload is rendered, not fetched, so `getUploadedFiles()` is not browser-callable

Filament exposes it as a `#[Renderless]` Livewire method the JS awaits. Our seam
answers through a browser event and cannot return a value to an awaiting call,
so the payload is rendered into the markup on every render and read back off the
`uploadedFiles` Stimulus value — the same stance the [select](select.md) takes
for its options: the server render is the truth. `getUploadedFiles()` is public
PHP and nothing more.

### Visibility defers to the storage, and a private file never falls through to a public URL

Filament infers `public`/`private` from the Laravel disk *name* (`'public'`),
which has no flysystem referent. Ours: `getVisibility(): ?string` returns the
custom value or `null`; a write passes `visibility` only when it is set, so the
storage's own configured default applies otherwise, and `setVisibility()` runs
after the store only for a custom value (catching `UnableToSetVisibility`, since
not every adapter has the notion). `getCustomVisibility()` has nothing left to
distinguish and is skipped.

The URL arm reads `getVisibility() ?? $storage->visibility($path)`, with an
unreadable visibility read as **private**, and the private arm returns `null`
rather than falling through to `publicUrl()` — otherwise a storage that cannot
mint a temporary URL would hand the browser a public link to a file the user
marked private. Both `temporaryUrl()` and `publicUrl()` are guarded by
`method_exists()`, since neither is on `FilesystemOperator`, and by their
`UnableToGenerate*` exceptions. Every URL leaves through
[`sanitize_url()`](../support/helpers.md).

**Recorded gap:** no serving route ships. A private file on a local storage gets
no URL at all — no thumbnail, no open, no download — until a
`temporary_url_generator` is configured. Filament's `FilePreviewController` twin
is deferred with the rich editor.

### `disk()` names a flysystem storage, and there is no `public`→`local` swap

`disk('public.storage')` names a storage service directly, through support's
[`FilesystemRegistry`](../support/filesystem-registry.md); an unnamed disk falls
back to `sylament.default_filesystem_disk`. Filament's extra arm — a
`default_filesystem_disk` of `'public'` with a `visibility('private')` field
silently becoming the `'local'` disk — is Laravel-only and is not ported. The
storage is memoised per resolved **name** rather than per instance, since
`disk()` may name a closure.

### The client-side size ceiling is the minimum of the field's and PHP's

`getMaxUploadSize()` — declared on the base, since it is the PHP ceiling and
nothing FilePond-specific — is
`(int) min(UploadedFile::getMaxFilesize(), PHP_INT_MAX)`, the narrowing being
what keeps the `int` return type true at level max: the static is `int|float`
and returns a float past `PHP_INT_MAX`. The template's `maxFileSize` value is
the minimum of that and `maxSize() * 1024`, in **bytes**, where Filament passes
`"{maxSize}KB"` and nothing else. The reason is that exceeding `post_max_size`
empties `$_POST`, at which point the live request fails with a JSON parse error
and an error overlay rather than a field message — so the browser has to refuse
the file first.

`minSize()` keeps Filament's `"{n}KB"` string, which is FilePond's own unit
spelling.

### `imageEditorEmptyFillColor` takes no array

Filament's property is `string | array | Closure | null` while the setter that
writes it takes `string | Closure | null` and the getter that reads it returns
`?string` — the array arm is reachable by no public member and would reach the
browser as a JSON array the controller has no branch for. Ours is
`string|Closure|null` throughout, so the type says what the surface does.

### The circle cropper refuses an accept list without PNG at render

`hasCircleCropper()` throws an `InvalidArgumentException` when the cropper is on
and `acceptedFileTypes()` is filled with a list admitting neither `image/png`
nor `image/*`. The editor's output is always a PNG, so such a field would let
the picker through and then refuse every *edited* file client-side, on a message
about the wrong type — the plugin validates the re-added file, whose type is
`image/png` whatever the original was. Filament has no such guard and fails the
same silent way; a getter that throws is how the rest of this family already
reports a misconfiguration (`shouldAutomaticallyOpenImageEditorForAspectRatio()`
carries three of Filament's own), so the failure surfaces on the first render
instead. An empty or absent accept list admits everything and passes.

### `getImageEditorActions()` carries methods and params, not Alpine

Filament's entries are `label` / `iconHtml` / `alpineClickHandler`, the last a
string of Alpine source (`editor.setDragMode('move')`) the markup drops into an
`x-on:click`. Ours are `label` / `icon` / `alias` / `method` / `params`: the
partial binds each button through `stimulus_action(controller, action.method,
'click', action.params)`, so the same thirteen actions in the same three groups
(`zoom`, `move`, `transform`) reach the controller as a method name plus a param
map. The vocabulary is fixed — `editorSetDragMode {mode}`, `editorZoom {ratio}`,
`editorZoomTo {ratio}`, `editorMove {x, y}`, `editorRotate {degrees}`,
`editorFlip {axis}` — and a consumer overriding the getter extends the toolbar
within it, which is what the Alpine strings amount to. `icon` is an icon name
for the nine heroicons and a `Twig\Markup` for the four SVGs Filament ships as
files of its own (`arrows-move`, `crop`, `flip-horizontal`, `flip-vertical`),
inlined here as private constants because no bundle registers an icon set with
ux-icons; `generateIconHtml()` renders the `Markup` arm raw.

`getImageEditorAspectRatioOptionsForJs()` carries two deltas of its own. It
normalises each ratio before calculating it, so `16/9` and `1.5` are accepted
where Filament's — which calculates straight off the raw `x:y` string — drops
them; the label keeps Filament's `str_replace('/', ':', …)` form, computed from
the raw value. And a numeric-only entry such as `'2'` becomes an **integer**
array key at runtime, in this port and in Filament alike, which the declared
`array<string, float|string>` does not say; it renders identically, so the
docblock keeps Filament's shape rather than widening to `array-key`.

### The editor is a `<dialog>` island, not an overlay

Filament renders the editor as a `role="dialog"` div pinned with `fixed inset-0
z-50`, focus-trapped by `x-trap.noscroll`, closed by an Escape handler and
backed by a decorative overlay div. Ours is a native `<dialog>` the controller
opens with `showModal()`, and every one of those four pieces is the platform's
instead: the top layer replaces the z-index (and cannot be clipped by a
transformed or overflow-hidden ancestor, which a `position: fixed` div can), the
browser's own focus trap and inert background replace `x-trap`, the `cancel`
event replaces the Escape handler, and `::backdrop` replaces the overlay div —
which had no click handler in Filament either. That is the mechanism support's
[`Modal`](../support/modal.md) already runs on; the editor does not use `Modal`
itself, because its client-side open path is a window event matched by id, its
width presets and header/footer slots do not fit a full-viewport
image-left/panel-right layout, and Filament likewise keeps the editor outside
its own modal component — the editor is field chrome, not a modal.

The **island** is the second half of the divergence and it is forced by the
morph: Cropper injects its `.cropper-container` DOM beside the `<img>`, and
outside a `data-live-ignore` subtree UX Live's `beforeNodeMorphed` inserts the
server's node *after* a client-added one rather than replacing it, duplicating
the whole editor on every render. Two costs, both recorded rather than fixed.
The dialog's static chrome is first-render only, so a closure-valued
`imageEditorAspectRatioOptions()` cannot react to a sibling field's state —
exactly as in Filament — and `disabled()` flipping on through a live update
leaves the dialog in the DOM, because a live-ignored node is never removed; the
controller's `disabled` gate is what refuses to open it (flipping off adds the
dialog, since added nodes are not blocked). And there is no body-scroll lock:
support's modal has none either, so `noscroll`'s half of `x-trap` is the one
piece of Filament's overlay with no replacement here.

### The bundle drops FilePond's mime database

`mimeTypeMap()` ships as the extension→MIME override it is, and it is the only
thing standing in for the `mime` database Filament bundles. One case degrades: a
file the browser reports no type for, whose extension is not in the map, is
handed to `fileValidateTypeDetectType` with nothing to go on. The server-side
`File(mimeTypes:)` constraint still refuses it, so the failure mode is a
server-side refusal rather than a client-side one.

### The three maps are `(object)`-cast so an empty one serialises as `{}`

`getExtraTemplateData()` returns `uploadedFiles`, `stateMap` and `mimeTypeMap`
cast to `stdClass`. `StimulusAttributes::getFormattedValue()` JSON-encodes a
non-scalar value, and PHP cannot tell an empty map from an empty list — so a
bare `[]` would hand the controller's `Object` value an array. That is not a
cosmetic mismatch: Stimulus's `object` reader **throws** on an array, and the
first read sits inside `#detectType`, whose synchronous throw the type plugin's
promise chain turns into a refusal — an uncast empty `mimeTypeMap()` made every
file on an `acceptedFileTypes()` field "of invalid type" client-side, which is
how the M3 · 7 browser pass found it (the third map shipped uncast; the render
test had pinned only the first two). There is no in-repo precedent (`Select`'s
option map is never empty-and-object), so this is a deliberate new one; the
render test pins all three as `{}`.

A new value, `transformImages`, is a plain boolean; no fourth map exists — the
aspect-ratio options render as buttons.

The same encoding rule runs one level down on the `Array` side, which is why
`getAcceptedFileTypes()` now returns `array_values()` on both arms: a *keyed*
iterable or array — the only latent hazard the epic's cast audit found — encodes
as `{}`-shaped JSON and Stimulus's `array` reader throws on it exactly as its
`object` reader throws on a list.

The same method must not use `filter_var()` anywhere in the `maxFileSize`
expression: with no filter it returns a **string**, which `min()` would then
compare as a string and Stimulus would emit into a `Number` value.

### Two Filament defects in the store pass are not ported

1. `saveUploadedFiles()` reads the raw `$this->isReorderable` property rather
   than calling `isReorderable()`, so a `reorderable(fn () => false)` closure is
   truthy as an object and the reorder hook fires regardless. Ours calls the
   getter.
2. The same method's `array_filter` drops every falsy stored path, so a
   legitimate path of `'0'` disappears. Ours drops only a `null` or blank
   return.

A third shape has no equivalent rather than being a defect: when
`saveUploadedFileUsing` is nulled out, Filament deletes the temporary file and
leaves the `TemporaryUploadedFile` **object** in state, which is then written to
the record. Ours deletes the file and drops the entry, since a token whose bytes
have just been deleted is a dangling handle.

### A generator that names nothing throws

`getUploadedFileNameForStorage()` throws a `LogicException` when
`getUploadedFileNameForStorageUsing()` was nulled out or returned a blank name,
where Filament would carry the null into `storeAs()`. Fail fast: a file about to
be written needs a name, and the alternative is a file stored at the directory
root under an empty name.

### The `Arrayable` arm of `acceptedFileTypes()` becomes `iterable`

Filament accepts `array|Arrayable|Closure` and unwraps the `Arrayable`. There is
no Symfony twin for that interface, so the signature is `iterable|Closure` and
`getAcceptedFileTypes()` converts a `Traversable` with `iterator_to_array()`.
Every array call site is unchanged.

### The three services arrive by `#[Required]` setter, not by facade

Filament reaches `Storage::`, `Validator::` and Livewire's temporary-upload
configuration through facades. Ours takes `TemporaryUploads`, `FilesystemRegistry`
and `ValidatorInterface` through `#[Required]` setters, which the container calls
because every component is a registered, non-shared service. A field built with
`new` therefore cannot upload: the first two getters throw a `LogicException`
naming the field and saying so, while `getValidator()` falls back to
`Validation::createValidator()` — the same shape
[`RecordTypeReader`](../schemas/record-type-reader.md) uses, and enough to keep
`getUploadedFileConstraints()` usable outside a container.

### The filename map's path needs no `nullable` entry

Filament's `dehydrateValidationRules()` adds `['nullable']` for the sibling
filename path so Laravel's validator does not treat the key as absent-and-
required. Our runner validates only the paths a component **declares**, so an
undeclared key is already unvalidated and the override has nothing to add.

### `uploadingMessage()` has no render site

`HasUploadingMessage` renders on the **submit button**, through a
`form-processing-started` channel between the form and the surrounding action
that support does not have. The row is blocked on that channel rather than on
anything about file uploads.

### The upload action is closed by construction, so there is nothing to restrict

Filament's `RestrictsFileUploadsToSchemaComponents` exists because Livewire opens
every property path to an upload. Our
[`uploadSchemaComponentFile()`](../schemas/interacts-with-schemas.md) resolves
the component through `getSchemaComponent()`, checks it against the
`AcceptsUploadedFiles` contract, and shape-checks the file key before it becomes
a state key — there is no open path to restrict.

### Literals, not translation keys

Per the milestone's carried decision (4), user-facing text is a literal here and
is listed so a later i18n pass can find every one: `Download` and
`Open in new tab` (the two item action labels the controller builds anchors
for), `The %s field must not have more than %d items.` (the max-files message,
`sprintf`-formatted with the field's label), and
`The submitted file path is not authorised.` (the tampering violation).
`The file could not be uploaded.` is the controller-side default for a refusal
that arrives with no message and lives with the JS.

The editor adds the rest. On the dialog itself: `Image editor` (its
`aria-label`), the footer's `Cancel`, `Reset` and `Save`, the five field labels
`X` / `Y` / `Width` / `Height` / `Rotation` with the units `px` and `deg`, the
aspect-ratio group's title `Aspect ratios`, the free-ratio option's label
`Free`, and each ratio button's `Set aspect ratio to <ratio>` title. The
thirteen toolbar labels come from `getImageEditorActions()` and serve as both
`aria-label` and `title`: `Drag mode "move"`, `Drag mode "crop"`, `Zoom in`,
`Zoom out`, `Zoom image to 100%`, `Move image to left`, `Move image to right`,
`Move image up`, `Move image down`, `Rotate image to left`,
`Rotate image to right`, `Flip image horizontally`, `Flip image vertically`. Two
more ride to the browser as Stimulus values, because the controller shows them
in a `confirm()` and an error: `Editing SVG files is not recommended as it can
result in quality loss when scaling.` followed by a newline and `Are you sure
you want to continue?` — Filament's single-quoted PHP leaves a literal
backslash-n there that renders as two characters, so ours carries the newline
that was meant — and `Editing SVG files is disabled as it can result in quality
loss when scaling.`

### The edited file's extension follows its type

Filament renames only the SVG arm of the editor's output (`svg` → `png`) and
otherwise keeps the source extension, so a circle-cropped `photo.jpg` comes back
as `photo-v1.jpg` carrying PNG bytes. The lie survives the whole pipeline:
[`TemporaryUploads::store()`](../../../packages/forms/src/Uploads/TemporaryUploads.php)
derives the on-disk extension from the **client** name while the sidecar records
the **sniffed** type, and `getUploadedFileNameForStorage()` keeps the client
extension in both arms — so parity would hold a `.jpg` in the temporary store,
save a `.jpg` (or `photo-v1.jpg` under `preserveFilenames()`) and report
`image/png` for it. Livewire's store has the same shape, so Filament ships that
mismatch too.

We rename at the origin instead, and after the encoder rather than before it.
The name follows the type the blob actually carries: `versionedName()` keeps the
source extension when that extension already denotes the blob's type and writes
the canonical one otherwise — `png`, `jpg` or `webp`, the only three a canvas
encodes. So a circle-cropped `photo.jpg` still comes back as `photo-v1.png`, and
the arm Filament's rule cannot reach is covered with it: `toBlob()` silently
falls back to PNG for any type the engine cannot encode — `image/gif`,
`image/bmp`, `image/tiff`, WebP where it is unsupported, or the empty type a
stored item's `fetch()` may hand back — so keying the rename to the *requested*
type would name PNG bytes `photo-v1.gif`, the same lie one source further along.
One expression, the server untouched, and M3·7's naming semantics intact.

Everything else in `saveEditor()` ports verbatim, including three things worth
not "fixing". The circle mask is an ellipse inscribed in the crop box, so a
non-square crop yields an oval rather than a circle — Filament's. `toBlob()` is
called with no quality argument, so a JPEG source re-encodes at the canvas
default. And the version bump matches `-v(\d+)` **unanchored**, so
`report-v2-final.png` bumps the first match and becomes `report-v3-final.png`
instead of gaining a `-v1` at the end — Filament's quirk, kept for parity and
written down here rather than silently corrected. The transform plugin's own
rename-on-type-change (its `ExtensionMap`) is never reached, because
`imageTransformOutputMimeType` is never set.

### The editor reads the dimension-fixed SVG, not the original

`#fixImageDimensions()` exists to give Cropper something to measure: an SVG
sized only by its `viewBox` has an intrinsic ratio and no intrinsic size, so the
function rewrites the markup with `width`/`height` in `pt` taken from the
viewBox and hands the rewritten copy on. Filament then reads the **original**
into the `FileReader` that feeds the editor (`file-upload.js:808`), so the copy
reaches only `editingFile`, the file the save is named and typed after — the
rewrite never touches what the user sees. Cropper is left measuring the browser's
fallback for a ratio-only replaced element (300×150 shrunk to the ratio, which
the browser pass watched open a 400×300 viewBox at 200×150). Ours reads the fixed
copy instead, so a viewBox-only SVG opens at its viewBox size and the crop box is
dragged over the dimensions the saved file will have. The function itself ports
verbatim, its `pt` unit and its three-spelling `viewBox` probe included.
