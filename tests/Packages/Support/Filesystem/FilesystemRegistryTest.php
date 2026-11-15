<?php

declare(strict_types=1);

use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Visibility;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Tests\Fixtures\Support\SignedUrlGenerator;
use Sylament\Tests\Fixtures\Support\VisibilityBlindAdapter;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\DependencyInjection\ServiceLocator;

it('collects the flysystem storages by name', function (): void {
    $registry = $this::getContainer()->get(FilesystemRegistry::class);
    $storage = $registry->get('primary.storage');
    $outOfOrder = new FilesystemRegistry(new ServiceLocator([
        'b.storage' => fn (): FilesystemOperator => $storage,
        'a.storage' => fn (): FilesystemOperator => $storage,
    ]));

    expect($registry->getNames())->toBe(['primary.storage', 'secondary.storage'])
        ->and($outOfOrder->getNames())->toBe(['a.storage', 'b.storage'])
        ->and($registry->has('secondary.storage'))->toBeTrue()
        ->and($registry->has('missing.storage'))->toBeFalse()
        ->and($registry->get('secondary.storage'))->toBeInstanceOf(FilesystemOperator::class)
        ->not->toBe($registry->get('primary.storage'));
});

it('answers no name with the configured default storage', function (): void {
    $registry = $this::getContainer()->get(FilesystemRegistry::class);

    expect($registry->get())->toBe($registry->get('primary.storage'));
});

it('refuses an unknown storage, naming it, the registered storages and both ways to register one', function (): void {
    $registry = $this::getContainer()->get(FilesystemRegistry::class);
    $empty = new FilesystemRegistry(new ServiceLocator([]));

    expect(fn (): FilesystemOperator => $registry->get('missing.storage'))->toThrow(
        LogicException::class,
        'No flysystem storage is registered as "missing.storage" (registered: primary.storage, secondary.storage). Configure it under flysystem.storages with league/flysystem-bundle, or tag a League\Flysystem\FilesystemOperator service "flysystem.storage" with storage: "missing.storage".',
    )
        ->and(fn (): FilesystemOperator => $empty->get('missing.storage'))->toThrow(LogicException::class, '(registered: none)');
});

it('refuses a tagged service that is not a FilesystemOperator', function (): void {
    $registry = new FilesystemRegistry(new ServiceLocator(['odd.storage' => fn (): stdClass => new stdClass()]));

    expect(fn (): FilesystemOperator => $registry->get('odd.storage'))
        ->toThrow(LogicException::class, 'The service tagged flysystem.storage as "odd.storage" is not a League\Flysystem\FilesystemOperator.');
});

it('has no fallback storage: no name and no configured default throws naming the setting, even with one storage registered', function (): void {
    $storage = $this::getContainer()->get(FilesystemRegistry::class)->get('primary.storage');
    $registry = new FilesystemRegistry(new ServiceLocator(['only.storage' => fn (): FilesystemOperator => $storage]));

    expect($registry->get('only.storage'))->toBe($storage)
        ->and(fn (): FilesystemOperator => $registry->get())->toThrow(
            LogicException::class,
            'No default disk is configured — set sylament.default_filesystem_disk to a flysystem storage name, or name the disk on the component.',
        );
});

it('gives a private file a temporary URL expiring the configured minutes from now', function (): void {
    $storage = new Filesystem(
        new LocalFilesystemAdapter(WorkerDirectory::path() . '/storage/signed'),
        temporaryUrlGenerator: new SignedUrlGenerator(),
    );

    $byDefault = $this::getContainer()->get(FilesystemRegistry::class)->getFileUrl($storage, 'report.txt', Visibility::PRIVATE);
    $configured = new FilesystemRegistry(new ServiceLocator([]), temporaryUrlExpiryMinutes: 5)->getFileUrl($storage, 'report.txt', Visibility::PRIVATE);

    expect($byDefault)->toStartWith('https://files.example.com/report.txt?expires=')
        ->and($configured)->toStartWith('https://files.example.com/report.txt?expires=');

    parse_str((string) parse_url((string) $byDefault, PHP_URL_QUERY), $default);
    parse_str((string) parse_url((string) $configured, PHP_URL_QUERY), $five);

    expect((int) $default['expires'])->toBeGreaterThan(time() + 29 * 60)->toBeLessThanOrEqual(time() + 30 * 60)
        ->and((int) $five['expires'])->toBeGreaterThan(time() + 4 * 60)->toBeLessThanOrEqual(time() + 5 * 60);
});

it('gives a public file its public URL, and never one to a private file or to one whose visibility it cannot read', function (): void {
    $registry = $this::getContainer()->get(FilesystemRegistry::class);
    $storage = $registry->get('primary.storage');
    $storage->write('private.txt', 'Private', [Config::OPTION_VISIBILITY => Visibility::PRIVATE]);
    $storage->write('public.txt', 'Public');

    $visibilityBlind = new Filesystem(new VisibilityBlindAdapter(), ['public_url' => '/files']);

    expect($registry->getFileUrl($storage, 'public.txt'))->toBe('/files/public.txt')
        ->and($registry->getFileUrl($storage, 'private.txt'))->toBeNull()
        ->and($visibilityBlind->publicUrl('report.txt'))->toBe('/files/report.txt')
        ->and($registry->getFileUrl($visibilityBlind, 'report.txt'))->toBeNull();
});
