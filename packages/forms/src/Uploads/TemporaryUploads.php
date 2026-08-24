<?php

declare(strict_types=1);

namespace Sylament\Forms\Uploads;

use InvalidArgumentException;
use JsonException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\WhitespacePathNormalizer;
use RuntimeException;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Holds a browser upload between the request that carried it and the form's save, on a flysystem
 * storage rather than in the live component's state — the bytes never travel through hydration.
 * The handle raw state carries is a `sylament-file:<hmac>:<filename>` token: the filename is 40
 * random hex characters, and the HMAC binds it to the state path of the field that validated it,
 * so a token minted for one field cannot be replayed into another.
 */
final readonly class TemporaryUploads
{
    public const string TOKEN_PREFIX = 'sylament-file:';

    private const int MAX_AGE_SECONDS = 86400;

    private const string FILENAME_PATTERN = '/^[0-9a-f]{40}\.[a-z0-9]{1,16}$/';

    private string $directory;

    public function __construct(
        private FilesystemRegistry $filesystems,
        private string $secret,
        private ?string $diskName = null,
        string $directory = 'sylament-tmp',
        private bool $cleanup = true,
    ) {
        $this->directory = new WhitespacePathNormalizer()->normalizePath($directory);

        if ($this->directory === '') {
            throw new InvalidArgumentException(sprintf(
                'The temporary upload directory cannot normalise to the storage root (given "%s"): prune() deletes everything older than 24 hours beneath it.',
                $directory,
            ));
        }
    }

    public function store(UploadedFile $file, string $statePath): TemporaryUploadedFile
    {
        $stripped = preg_replace('/[^A-Za-z0-9]/', '', $file->getClientOriginalExtension()) ?? '';
        $extension = mb_substr(mb_strtolower($stripped === '' ? 'bin' : $stripped), 0, 16);
        $filename = bin2hex(random_bytes(20)) . '.' . $extension;

        // Security: the sidecar records the *sniffed* type, never `getClientMimeType()` — the
        // browser's claim is attacker-controlled and everything downstream reads this back.
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        if ($size === false) {
            throw new RuntimeException(sprintf('The size of the uploaded file "%s" could not be read.', $file->getClientOriginalName()));
        }

        $disk = $this->getDisk();
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw new RuntimeException(sprintf('The uploaded file "%s" could not be opened for reading.', $file->getClientOriginalName()));
        }

        try {
            $disk->writeStream($this->pathOf($filename), $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $disk->write($this->sidecarPathOf($filename), json_encode([
            'name' => $file->getClientOriginalName(),
            'type' => $mimeType,
            'size' => $size,
        ], JSON_THROW_ON_ERROR));

        if ($this->cleanup) {
            $this->prune();
        }

        return new TemporaryUploadedFile(
            $this,
            $filename,
            self::TOKEN_PREFIX . $this->sign($filename, $statePath) . ':' . $filename,
            $file->getClientOriginalName(),
            $mimeType,
            $size,
        );
    }

    /**
     * The token's own field is the only one that can resolve it; every other outcome — a foreign
     * prefix, a tampered signature, a filename outside the minted shape, a missing sidecar or
     * missing bytes — is null rather than an exception, because each of them is reachable from
     * the browser.
     */
    public function resolve(string $token, string $statePath): ?TemporaryUploadedFile
    {
        if (!self::isToken($token)) {
            return null;
        }

        $parts = explode(':', mb_substr($token, mb_strlen(self::TOKEN_PREFIX)), 2);

        if (count($parts) !== 2 || !hash_equals($this->sign($parts[1], $statePath), $parts[0])) {
            return null;
        }

        [, $filename] = $parts;

        if (preg_match(self::FILENAME_PATTERN, $filename) !== 1) {
            return null;
        }

        $disk = $this->getDisk();

        try {
            if (!$disk->fileExists($this->pathOf($filename)) || !$disk->fileExists($this->sidecarPathOf($filename))) {
                return null;
            }

            $metadata = json_decode($disk->read($this->sidecarPathOf($filename)), true, flags: JSON_THROW_ON_ERROR);
        } catch (FilesystemException | JsonException) {
            return null;
        }

        if (!is_array($metadata)) {
            return null;
        }

        $name = $metadata['name'] ?? null;
        $type = $metadata['type'] ?? null;
        $size = $metadata['size'] ?? null;

        if (!is_string($name) || !is_int($size) || ($type !== null && !is_string($type))) {
            return null;
        }

        return new TemporaryUploadedFile($this, $filename, $token, $name, $type, $size);
    }

    public static function isToken(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::TOKEN_PREFIX);
    }

    public function getDiskName(): string
    {
        return $this->diskName ?? $this->filesystems->getDefaultDiskName();
    }

    public function getDisk(): FilesystemOperator
    {
        return $this->filesystems->get($this->diskName);
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function pathOf(string $filename): string
    {
        return $this->directory . '/' . $filename;
    }

    public function sidecarPathOf(string $filename): string
    {
        return $this->pathOf($filename) . '.json';
    }

    /**
     * Delete everything under the temporary directory older than 24 hours, and answer how many
     * entries went. It rides on upload traffic — there is no scheduled job — so a store nobody
     * writes to is never swept.
     */
    public function prune(): int
    {
        $deleted = 0;
        $cutoff = time() - self::MAX_AGE_SECONDS;
        $disk = $this->getDisk();

        try {
            // `listContents()` hands back a lazy listing whose generator raises only on
            // iteration, so the loop — not the call — is what the try has to cover.
            foreach ($disk->listContents($this->directory, deep: true) as $item) {
                if (!$item->isFile()) {
                    continue;
                }

                try {
                    if (($item->lastModified() ?? $disk->lastModified($item->path())) < $cutoff) {
                        $disk->delete($item->path());
                        $deleted++;
                    }
                } catch (UnableToRetrieveMetadata | UnableToDeleteFile) {
                    // Another process took it — the sweep races by design.
                }
            }
        } catch (FilesystemException) {
            return $deleted;
        }

        return $deleted;
    }

    private function sign(string $filename, string $statePath): string
    {
        return mb_substr(hash_hmac('sha256', $filename . "\0" . $statePath, $this->secret), 0, 32);
    }
}
