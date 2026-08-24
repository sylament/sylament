# Temporary uploads (forms)

`Sylament\Forms\Uploads\TemporaryUploads` and its value object
`Sylament\Forms\Uploads\TemporaryUploadedFile` — where a browser upload lives
between the request that carried it and the form's save. Filament has no such
class: it leans on **Livewire's** `SupportFileUploads` feature, so this is a
port of Livewire, not of Filament, and the only piece of the file-upload
vertical with no `vendor/filament` counterpart at all. The bytes go straight to
a flysystem storage; what the field's raw state carries is a
`sylament-file:<hmac>:<filename>` **token**, so nothing binary ever travels
through live-component hydration. The store also owns the 24-hour sweep that
keeps abandoned uploads from accumulating, and the `sylament_forms` config tree
that names its disk and directory — the toolkit's
[first package-specific tree](forms-bundle.md).

The field that mints and redeems these tokens is
[`FileUpload`](file-upload.md); the constraint filed beside them,
[`MinFileSize`](can-be-validated.md), is here because Symfony's `File` caps a
size and never floors it.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Livewire source this is diffed against:

- [`SupportFileUploads/FileUploadConfiguration.php`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php)
  — the disk/directory resolution and the whitespace path normalisation
- [`SupportFileUploads/TemporaryUploadedFile.php`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php)
  — the value object that extends `UploadedFile` and the `-meta<base64>-`
  filename scheme, neither of which we take
- [`SupportFileUploads/WithFileUploads.php`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/WithFileUploads.php)
  · [`GenerateSignedUploadUrl.php`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/GenerateSignedUploadUrl.php)
  — the three-request dance and its signed URL, replaced by one live request;
  `cleanupOldUploads()` on the trait is the sweep `prune()` ports
- [`config/livewire.php`](../../../vendor/livewire/livewire/config/livewire.php)
  — the `temporary_file_upload` config block the `sylament_forms` tree maps onto

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics) or [Divergences](#divergences), or `—`. A row whose
_Ports_ cell is `—` has no Livewire counterpart.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`store(UploadedFile $file, string $statePath): TemporaryUploadedFile`](../../../packages/forms/src/Uploads/TemporaryUploads.php) | [`FileUploadConfiguration::path()`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php) | done | — | [semantics](#the-sidecar-is-what-carries-the-original-name-and-the-sniffed-type) |
| [`resolve(string $token, string $statePath): ?TemporaryUploadedFile`](../../../packages/forms/src/Uploads/TemporaryUploads.php) | — | done | — | [semantics](#resolve-answers-null-for-every-failure-because-every-failure-is-reachable-from-the-browser) |
| [`isToken(mixed $value): bool`](../../../packages/forms/src/Uploads/TemporaryUploads.php) | — | done | — | [semantics](#istoken-is-a-prefix-test-not-a-validity-test) |
| [`prune(): int`](../../../packages/forms/src/Uploads/TemporaryUploads.php) | [`WithFileUploads::cleanupOldUploads()`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/WithFileUploads.php) | done | — | [semantics](#the-sweep-rides-on-upload-traffic-and-races-itself-on-purpose) |
| [`getDiskName()` / `getDisk()` / `getDirectory()` / `pathOf()` / `sidecarPathOf()`](../../../packages/forms/src/Uploads/TemporaryUploads.php) | [`FileUploadConfiguration::disk()` / `::directory()`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php) | done | — | — |
| [`TOKEN_PREFIX`](../../../packages/forms/src/Uploads/TemporaryUploads.php) and the token format | — | done | — | [semantics](#what-the-hmac-binds-is-the-field-not-the-user) |
| [`TemporaryUploadedFile`'s metadata getters](../../../packages/forms/src/Uploads/TemporaryUploadedFile.php) — `getToken()`, `getFilename()`, `getPath()`, `getDiskName()`, `getClientOriginalName()`, `getClientOriginalExtension()`, `getMimeType()`, `getSize()` | [`TemporaryUploadedFile`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php) | done | — | [diverges](#temporaryuploadedfile-is-a-value-object-rather-than-an-uploadedfile) |
| [`TemporaryUploadedFile::exists()` / `readStream()` / `delete()`](../../../packages/forms/src/Uploads/TemporaryUploadedFile.php) | [`TemporaryUploadedFile`](../../../vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php) | done | — | — |
| [`TemporaryUploadedFile::jsonSerialize()` / `__toString()`](../../../packages/forms/src/Uploads/TemporaryUploadedFile.php) | — | done | — | [semantics](#both-serialisations-answer-the-token-so-raw-state-holds-one-string) |
| [`sylament_forms.temporary_file_upload.{disk,directory,cleanup}`](../../../packages/forms/src/SylamentFormsBundle.php) | [`config/livewire.php`](../../../vendor/livewire/livewire/config/livewire.php) | done | — | [diverges](#the-config-tree-keeps-three-of-livewires-seven-keys) |
| A console command that prunes on a schedule | — | skipped | — | [diverges](#there-is-no-prune-command-the-sweep-rides-on-upload-traffic) |

Coverage:
[`TemporaryUploadsTest`](../../../packages/forms/tests/Unit/Uploads/TemporaryUploadsTest.php)
— the written bytes and sidecar, the token shape, the mime sniff overriding the
browser's claim, the same-state-path round trip, and the four refusals
(a foreign prefix, a tampered signature, a swapped filename, a filename outside
the minted shape, including a traversal and a sidecar name under a *valid*
signature); a missing sidecar and missing bytes; the extension sanitiser across
seven names; the sweep's age boundary, its `cleanup` flag on both settings, and
its zero answer for a directory nothing has written to; and the constructor's
refusal of a directory that normalises to the storage root.
[`TemporaryUploadedFileTest`](../../../packages/forms/tests/Unit/Uploads/TemporaryUploadedFileTest.php)
— the metadata getters, both serialisations, the stream read-back, a deleted
file reporting gone rather than throwing, and `delete()` taking the sidecar with
it and swallowing a second call.
[`SylamentFormsBundleTest`](../../../packages/forms/tests/Integration/SylamentFormsBundleTest.php)
— the store is a container service carrying the config tree's directory.

Both `Unit/Uploads` suites build their stores through
[`TemporaryUploadsRig`](../../../packages/forms/tests/Unit/Uploads/TemporaryUploadsRig.php)
rather than the file-level Pest helper functions used everywhere else in the
monorepo, because two files need the same rig and a helper declared in one of
them exists only while the other is loaded too — which a full run does and a
single-file run does not. The rig also tracks every path it puts on disk so each
suite's `afterEach` can remove it: these are the first tests here to write
outside the kernel's cache directory, and nothing rolls a unit test's disk
writes back.

## Semantics

### What the HMAC binds is the field, not the user

The signature is `hash_hmac('sha256', "$filename\0$statePath", kernel.secret)`,
truncated to 32 characters, and `resolve()` recomputes it with `hash_equals()`.
The `statePath` is the field's absolute state path — `data.coverImage` — which
is **the same string for every user on that form**. So the binding stops a token
minted for one field being replayed into another, and stops a hand-written token
naming an arbitrary path; it does **not** stop user A redeeming a token user B
minted, because nothing user-specific goes into it.

What does stop that is the filename: 40 hexadecimal characters from
`random_bytes(20)`, never derived from anything the client sent. An attacker who
cannot guess the name cannot mint the signature for it either, since the secret
is the server's. This is exactly Livewire's posture — its temp filenames are
`Str::random(...)` and its endpoint is not per-user either — and it is why the
temp directory must not be publicly listable.

### `resolve()` answers null for every failure because every failure is reachable from the browser

A token arrives in raw state, and raw state arrives from the client. So the four
refusals — a value that is not a token, a signature that does not match this
state path, a filename outside `/^[0-9a-f]{40}\.[a-z0-9]{1,16}$/`, and bytes or
sidecar that are not there — are all *expected* inputs rather than programmer
errors, and each answers `null`. The shape check is not redundant with the
signature: the signature only proves that *this server* signed the string, and
`store()` is not the only thing that could ever be made to sign one. It is what
keeps `../secret.env` and `<filename>.json` from being resolvable paths.

### The sidecar is what carries the original name and the sniffed type

The stored name is random, so the display name has to live somewhere. It goes in
a second object beside the bytes, `<filename>.json`, holding `name`, `type` and
`size`. `type` is `UploadedFile::getMimeType()` — the **sniffed** type from
`symfony/mime`, never `getClientMimeType()`, which is the browser's claim and
attacker-controlled. `resolve()` re-validates the decoded shape before trusting
it, because the sidecar is an object on a storage a host application also writes
to.

The sidecar is a plain file on the same disk, so the sweep ages it out
independently of its bytes. That is harmless — either half missing makes
`resolve()` answer `null` — and it is why `TemporaryUploadedFile::delete()`
removes both and shrugs at a second call.

### The sweep rides on upload traffic and races itself on purpose

`store()` calls `prune()` after a successful write when `cleanup` is on, which
is Livewire's policy verbatim: there is no scheduled job, so a store nobody
writes to is never swept. Two consequences worth stating rather than deriving.
A deployment with no uploads for a week keeps week-old temp files. And two
requests can sweep at once, which is why the per-item `UnableToRetrieveMetadata`
/ `UnableToDeleteFile` catches are the design rather than a defensive habit —
whoever got there first won, and an `exists()` pre-check would only add a round
trip to a remote adapter without closing the race.

The directory is therefore not allowed to normalise to the storage root: the
constructor throws for `''`, `'/'` and `'.'`, because `prune()` sweeps
everything beneath whatever it is pointed at, and pointing it at the root would
quietly delete a host application's own day-old files. Note that Flysystem's
`WhitespacePathNormalizer` rejects control characters and collapses separators
but does **not** trim spaces, so a directory of three spaces is a real directory
and passes.

`listContents()` hands back a lazy `DirectoryListing`, and its generator raises
`UnableToListContents` on **iteration**, not on the call. The `try` therefore
wraps the `foreach`, not the `listContents()` line. Getting that wrong would
turn an unreadable temp directory into a 500 on every upload, since `store()`
prunes on success — the failure would surface at the upload, not at the sweep.

### `isToken()` is a prefix test, not a validity test

`isToken()` answers whether a value *looks like* one of our tokens — a string
starting with `sylament-file:` — and nothing more. It exists so a caller holding
a raw-state entry can tell "a temp upload" from "a stored path" without touching
the disk, which is what the field's save-time and tampering-gate branches need.
Only `resolve()` decides whether a token is real.

### Both serialisations answer the token, so raw state holds one string

`jsonSerialize()` and `__toString()` both return the token. That is what lets
the field assign a `TemporaryUploadedFile` straight into raw state and have the
dehydrated bag carry a plain string — no cast, no branch at the call site, and
the same value on the wire whether the entry was just uploaded or read back.

## Divergences

### `TemporaryUploadedFile` is a value object rather than an `UploadedFile`

Livewire's class extends `Illuminate\Http\UploadedFile` and then contradicts
most of it — `getRealPath()` names a path that only exists on a local adapter,
`isValid()` is hardcoded, the constructor opens a `tmpfile()` purely to satisfy
the parent. Symfony's hierarchy is stricter still: `UploadedFile extends File
extends SplFileInfo`, and `SplFileInfo` resolves a real path in its constructor,
which a file on a remote storage does not have. So ours is a plain final value
object over the temp disk, carrying the getters the field actually calls.

This is [principles §1](../../principles.md) — Symfony idiom over a Laravel
internal — not an API change: the method names a migrating user greps for
(`getClientOriginalName()`, `getClientOriginalExtension()`, `getMimeType()`,
`getSize()`, `exists()`, `delete()`) are unchanged. Two of Filament's reads are
`protected` properties it reaches by closure rebinding; those are real getters
here, `getDiskName()` and `getPath()`.

### One live request carries the bytes, so there is no signed upload URL and no progress bar

Livewire needs three requests — `_startUpload`, a signed XHR to its own
endpoint, `_finishUpload` — because its update endpoint speaks JSON only. UX
LiveComponent's already speaks multipart, so the file rides the same request as
the action call. Three Livewire features go with the dance:

- **The signed, time-limited upload URL** (`max_upload_time`, default 5 minutes)
  has nothing to sign — there is no separate endpoint, and the live route's own
  session boundary applies instead.
- **XHR upload progress** is gone. UX Live uses `fetch()`, which has no
  upload-progress event, so the browser shows an indeterminate indicator rather
  than a percentage. This is a visible regression against Filament and is stated
  here rather than left to be discovered.
- **Upload cancellation** has no seam: a live request is not exposed for
  aborting, so a cancelled item is removed once the request settles rather than
  aborted in flight.

### The sidecar replaces Livewire's `-meta<base64>-` filename scheme

Livewire encodes the original filename into the temp filename itself, as
`-meta<base64>-`, so that a stateless S3 endpoint can recover it. We are never
stateless about it — the same request that writes the bytes writes the sidecar —
and a metadata-bearing filename is a parser at exactly the place we want an
opaque random string. Livewire only uses the scheme on S3 anyway.

### The config tree keeps three of Livewire's seven keys

`sylament_forms.temporary_file_upload` carries `disk` (null → the toolkit-wide
`sylament.default_filesystem_disk`), `directory` (`sylament-tmp`, whitespace-
normalised through Flysystem's `WhitespacePathNormalizer` exactly as Livewire
normalises its own) and `cleanup` (`true`). The other four do not survive:
`rules` becomes the field's own `maxSize()`/`minSize()`/`acceptedFileTypes()`
constraints, which run at upload time; `middleware` and `max_upload_time` belong
to the endpoint we do not have; and `preview_mimes` is only ever read by
Livewire's preview route, which [`FileUpload`](file-upload.md) never calls.

It lives under the derived `sylament_forms` alias rather than `sylament`,
because a bundle cannot extend another bundle's config tree — see
[`docs/configuration.md`](../../configuration.md).

**Multi-server deployments need a shared temp storage or sticky sessions.** The
upload request and the save request are different requests; with a local temp
disk on two nodes, the second one cannot see what the first wrote. Livewire has
the identical hole and answers it with "use S3"; the honest answer here is the
same, and it is why the `disk` key exists separately from the destination disk.

### There is no prune command; the sweep rides on upload traffic

Neither Livewire nor Filament ships a console command for this, and we have not
added one, so the only thing that ages temp files out is a later successful
upload. A `sylament:forms:prune-temporary-uploads` command wrapping
`TemporaryUploads::prune()` is the obvious follow-up — it is a few lines and
Symfony makes it cron-able — and it is recorded here rather than built so the
gap is a decision rather than an oversight. What would reverse this: a
deployment where uploads are bursty enough that a week of stale files matters,
or a temp disk that bills for storage.
