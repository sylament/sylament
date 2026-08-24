<?php

declare(strict_types=1);

namespace Sylament\Forms\Uploads;

use JsonSerializable;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToDeleteFile;
use Stringable;

/**
 * One file held by {@see TemporaryUploads} between its upload and the form's save. It is a value
 * object over the temp disk, not a `Symfony\Component\HttpFoundation\File\UploadedFile`: the bytes
 * live on a flysystem storage that need not be local, so there is no path on this machine to hand
 * an `SplFileInfo`. It serialises to its token, which is what raw state holds.
 */
final readonly class TemporaryUploadedFile implements JsonSerializable, Stringable
{
    public function __construct(
        private TemporaryUploads $store,
        private string $filename,
        private string $token,
        private string $clientOriginalName,
        private ?string $mimeType,
        private int $size,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getPath(): string
    {
        return $this->store->pathOf($this->filename);
    }

    public function getDiskName(): string
    {
        return $this->store->getDiskName();
    }

    public function getClientOriginalName(): string
    {
        return $this->clientOriginalName;
    }

    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->clientOriginalName, PATHINFO_EXTENSION);
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function exists(): bool
    {
        try {
            return $this->store->getDisk()->fileExists($this->getPath());
        } catch (UnableToCheckFileExistence) {
            // A storage that cannot answer is treated as not holding the file, so a re-render
            // drops the item rather than failing the whole request.
            return false;
        }
    }

    /**
     * @return resource
     */
    public function readStream()
    {
        return $this->store->getDisk()->readStream($this->getPath());
    }

    public function delete(): void
    {
        $disk = $this->store->getDisk();

        foreach ([$this->getPath(), $this->store->sidecarPathOf($this->filename)] as $path) {
            try {
                $disk->delete($path);
            } catch (UnableToDeleteFile) {
                // The 24-hour sweep races every removal by design; whoever got there first won.
            }
        }
    }

    public function jsonSerialize(): string
    {
        return $this->token;
    }

    public function __toString(): string
    {
        return $this->token;
    }
}
