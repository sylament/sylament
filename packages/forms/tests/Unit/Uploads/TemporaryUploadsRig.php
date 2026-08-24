<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Uploads;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The rig both `Unit/Uploads` suites build their stores and fixture uploads from. It is a class
 * rather than the file-level helper functions the rest of the suite uses because two files need
 * it: a helper declared in one Pest file exists only while the other file is loaded too, which a
 * full run does and the documented single-file run does not.
 *
 * Every path it puts on disk is tracked, so `cleanUp()` can take it back; each suite calls that
 * from its own `afterEach`, because nothing rolls a unit test's disk writes back and these would
 * otherwise pile up in the system temp directory on every run.
 */
final class TemporaryUploadsRig
{
    /** @var list<Filesystem> */
    private static array $disks = [];

    /** @var list<string> */
    private static array $files = [];

    /**
     * A one-storage registry over a throwaway local directory, plus the directory itself so a
     * test can reach the bytes behind flysystem's back — `touch()` is how the sweep's clock moves.
     *
     * @return array{TemporaryUploads, string}
     */
    public static function make(bool $cleanup = true, string $directory = 'sylament-tmp'): array
    {
        $root = sys_get_temp_dir() . '/sylament-uploads-' . bin2hex(random_bytes(6));
        $disk = new Filesystem(new LocalFilesystemAdapter($root));
        self::$disks[] = $disk;

        $registry = new FilesystemRegistry(
            new ServiceLocator(['tmp.storage' => static fn (): FilesystemOperator => $disk]),
            'tmp.storage',
        );

        return [new TemporaryUploads($registry, 'secret', null, $directory, $cleanup), $root];
    }

    public static function uploadedFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sylament-upload-');

        if ($path === false) {
            throw new RuntimeException('Could not create a temporary file for the fixture upload.');
        }

        self::$files[] = $path;

        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    public static function pngBytes(): string
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            true,
        );

        if ($bytes === false) {
            throw new RuntimeException('The fixture PNG is not valid base64.');
        }

        return $bytes;
    }

    public static function cleanUp(): void
    {
        foreach (self::$files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // `deleteDirectory('')` is the adapter's own recursive removal of the storage root, and
        // it returns quietly when the directory was never written to — the constructor-guard
        // cases build a rig whose root never appears on disk.
        foreach (self::$disks as $disk) {
            $disk->deleteDirectory('');
        }

        self::$files = [];
        self::$disks = [];
    }
}
