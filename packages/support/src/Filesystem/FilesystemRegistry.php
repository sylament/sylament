<?php

declare(strict_types=1);

namespace Sylament\Support\Filesystem;

use League\Flysystem\FilesystemOperator;
use LogicException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The `Storage::disk()` translation: named flysystem storages, collected from the
 * `flysystem.storage` tag league/flysystem-bundle stamps on every configured storage (keyed by
 * its `storage` attribute), plus the toolkit-wide default disk and temporary-URL expiry from the
 * `sylament` config tree. A consumer without the bundle tags its own `FilesystemOperator` service
 * the same way.
 */
final readonly class FilesystemRegistry
{
    /**
     * @param ServiceLocator<object> $storages every service carrying the tag, operator or not
     */
    public function __construct(
        private ServiceLocator $storages,
        private ?string $defaultDiskName = null,
        private int $temporaryUrlExpiryMinutes = 30,
    ) {
    }

    public function get(?string $name = null): FilesystemOperator
    {
        $name ??= $this->getDefaultDiskName();

        if (!$this->storages->has($name)) {
            throw new LogicException(sprintf(
                'No flysystem storage is registered as "%s" (registered: %s). Configure it under flysystem.storages with league/flysystem-bundle, or tag a League\Flysystem\FilesystemOperator service "flysystem.storage" with storage: "%s".',
                $name,
                $this->getNames() === [] ? 'none' : implode(', ', $this->getNames()),
                $name,
            ));
        }

        $storage = $this->storages->get($name);

        if (!$storage instanceof FilesystemOperator) {
            throw new LogicException(sprintf('The service tagged flysystem.storage as "%s" is not a League\Flysystem\FilesystemOperator.', $name));
        }

        return $storage;
    }

    public function has(string $name): bool
    {
        return $this->storages->has($name);
    }

    public function getDefaultDiskName(): string
    {
        return $this->defaultDiskName ?? throw new LogicException('No default disk is configured — set sylament.default_filesystem_disk to a flysystem storage name, or name the disk on the component.');
    }

    public function getTemporaryUrlExpiryMinutes(): int
    {
        return $this->temporaryUrlExpiryMinutes;
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        $names = array_keys($this->storages->getProvidedServices());
        sort($names);

        return $names;
    }
}
