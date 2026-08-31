<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Filesystem;

use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use LogicException;
use stdClass;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @param array<string, FilesystemOperator> $storages
 */
function registryWith(array $storages, ?string $default = null): FilesystemRegistry
{
    $factories = [];

    foreach ($storages as $name => $operator) {
        $factories[$name] = static fn (): FilesystemOperator => $operator;
    }

    return new FilesystemRegistry(new ServiceLocator($factories), $default);
}

function localOperator(): Filesystem
{
    $directory = sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4));

    return new Filesystem(new LocalFilesystemAdapter($directory));
}

it('resolves a storage by name and the default', function (): void {
    $first = localOperator();
    $second = localOperator();
    $registry = registryWith(['a.storage' => $first, 'b.storage' => $second], 'b.storage');

    expect($registry->get('a.storage'))->toBe($first)
        ->and($registry->get())->toBe($second)
        ->and($registry->has('a.storage'))->toBeTrue()
        ->and($registry->has('nope'))->toBeFalse()
        ->and($registry->getNames())->toBe(['a.storage', 'b.storage'])
        ->and($registry->getDefaultDiskName())->toBe('b.storage')
        ->and($registry->getTemporaryUrlExpiryMinutes())->toBe(30);
});

it('throws naming the storage, the registered names and how to register one', function (): void {
    $registry = registryWith(['a.storage' => localOperator()]);

    expect(fn (): FilesystemOperator => $registry->get('missing.storage'))
        ->toThrow(LogicException::class, 'missing.storage')
        ->toThrow(LogicException::class, 'a.storage')
        ->toThrow(LogicException::class, 'flysystem.storage');
});

it('throws when no default disk is configured', function (): void {
    expect(fn (): FilesystemOperator => registryWith(['a.storage' => localOperator()])->get())
        ->toThrow(LogicException::class, 'sylament.default_filesystem_disk');
});

it('refuses a tagged service that is not a FilesystemOperator', function (): void {
    $registry = new FilesystemRegistry(new ServiceLocator(['x' => static fn (): object => new stdClass()]));

    expect(fn (): FilesystemOperator => $registry->get('x'))
        ->toThrow(LogicException::class, 'FilesystemOperator');
});

it('carries the configured temporary-url expiry', function (): void {
    $registry = new FilesystemRegistry(new ServiceLocator([]), temporaryUrlExpiryMinutes: 5);

    expect($registry->getTemporaryUrlExpiryMinutes())->toBe(5)
        ->and($registry->getNames())->toBe([]);
});

it('mints a public url for a public file', function (): void {
    $storage = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4))), ['public_url' => '/files']);
    $storage->write('covers/a.png', 'the bytes');

    expect(registryWith([])->getFileUrl($storage, 'covers/a.png', 'public'))->toBe('/files/covers/a.png');
});

it('reads an unset visibility from the storage', function (): void {
    $storage = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4))), ['public_url' => '/files']);
    $storage->write('covers/a.png', 'the bytes', ['visibility' => 'public']);

    expect(registryWith([])->getFileUrl($storage, 'covers/a.png'))->toBe('/files/covers/a.png');
});

it('treats an unreadable visibility as private and never falls through to a public url', function (): void {
    // The storage can mint a public url, but the visibility read throws — the private arm must
    // return null rather than hand out the public link.
    $adapter = new class (new LocalFilesystemAdapter(sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4)))) extends DecoratedAdapter {
        public function visibility(string $path): FileAttributes
        {
            throw UnableToRetrieveMetadata::visibility($path);
        }
    };
    $storage = new Filesystem($adapter, ['public_url' => '/files']);
    $storage->write('covers/a.png', 'the bytes');

    expect(registryWith([])->getFileUrl($storage, 'covers/a.png'))->toBeNull();
});

it('mints a temporary url for a private file using the configured expiry', function (): void {
    $generator = new class implements TemporaryUrlGenerator {
        public ?DateTimeInterface $expiresAt = null;

        public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
        {
            $this->expiresAt = $expiresAt;

            return 'https://signed.example/' . $path;
        }
    };
    $storage = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4))), temporaryUrlGenerator: $generator);
    $storage->write('covers/a.png', 'the bytes');

    $registry = new FilesystemRegistry(new ServiceLocator([]), temporaryUrlExpiryMinutes: 5);

    expect($registry->getFileUrl($storage, 'covers/a.png', 'private'))->toBe('https://signed.example/covers/a.png')
        ->and($generator->expiresAt?->getTimestamp())->toBeGreaterThan(time() + 4 * 60)
        ->and($generator->expiresAt?->getTimestamp())->toBeLessThan(time() + 6 * 60);
});

it('returns null for a private file the storage cannot sign', function (): void {
    $storage = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4))), ['public_url' => '/files']);
    $storage->write('covers/b.png', 'the bytes');

    expect(registryWith([])->getFileUrl($storage, 'covers/b.png', 'private'))->toBeNull();
});

it('returns null for a public file on a storage with no public url', function (): void {
    $storage = localOperator();
    $storage->write('covers/a.png', 'the bytes');

    expect(registryWith([])->getFileUrl($storage, 'covers/a.png', 'public'))->toBeNull();
});

it('sanitizes the minted url', function (): void {
    $generator = new class implements PublicUrlGenerator {
        public function publicUrl(string $path, Config $config): string
        {
            return 'javascript:alert(1)';
        }
    };
    $storage = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/sylament-registry-' . bin2hex(random_bytes(4))), publicUrlGenerator: $generator);
    $storage->write('covers/a.png', 'the bytes');

    expect(registryWith([])->getFileUrl($storage, 'covers/a.png', 'public'))->toBeNull();
});
