<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;
use Sylament\Tests\Infrastructure\WorkerDirectory;

/**
 * A storage that cannot tell whether a file exists, as an object store refusing a HEAD request does.
 */
final class ExistenceBlindAdapter extends LocalFilesystemAdapter
{
    public function __construct()
    {
        parent::__construct(WorkerDirectory::path() . '/storage/existence-blind');
    }

    public function fileExists(string $location): bool
    {
        throw UnableToCheckFileExistence::forLocation($location);
    }
}
