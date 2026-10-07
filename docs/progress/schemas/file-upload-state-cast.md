---
description: The FileUploadStateCast, which keys a file upload's state by uuid on write and reads it back as one file or a list.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/FileUploadStateCast.php
owns:
  - packages/schemas/src/State/Casts/FileUploadStateCast.php
---
# FileUploadStateCast (schemas)

[`Sylament\Schemas\State\Casts\FileUploadStateCast`](../../../packages/schemas/src/State/Casts/FileUploadStateCast.php) implements [`StateCast`](state-cast.md).
[`BaseFileUpload`](../forms/file-upload.md) appends it to its default casts, passing `isMultiple()`.
Its uuid and first-element calls follow two [idiom translations](../translations.md#idiom): `Str::uuid()` becomes `Uuid::v4()->toRfc4122()`, and `Arr::first()` becomes `array_first()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__construct(bool $isMultiple = false)`](../../../packages/schemas/src/State/Casts/FileUploadStateCast.php) | [`StateCasts/FileUploadStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/FileUploadStateCast.php) | done | — | — |
| [`get()` — the values as a list when multiple, otherwise the first file](../../../packages/schemas/src/State/Casts/FileUploadStateCast.php) | [`StateCasts/FileUploadStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/FileUploadStateCast.php) | done | — | — |
| [`set()` — blank files dropped, a numeric key replaced by a fresh uuid](../../../packages/schemas/src/State/Casts/FileUploadStateCast.php) | [`StateCasts/FileUploadStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/FileUploadStateCast.php) | done | — | — |
