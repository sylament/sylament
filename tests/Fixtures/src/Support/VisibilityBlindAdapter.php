<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

use League\Flysystem\FileAttributes;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToRetrieveMetadata;
use Sylament\Tests\Infrastructure\WorkerDirectory;

/**
 * A storage that keeps no visibility, as an object store with access lists turned off does.
 */
final class VisibilityBlindAdapter extends LocalFilesystemAdapter
{
    public function __construct()
    {
        parent::__construct(WorkerDirectory::path() . '/storage/visibility-blind');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'This storage keeps no visibility.');
    }
}
