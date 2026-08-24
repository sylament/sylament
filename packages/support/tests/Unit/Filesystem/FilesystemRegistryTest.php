<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
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
