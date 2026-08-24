<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Uploads;

use Sylament\Forms\Uploads\TemporaryUploadedFile;

it('carries the upload metadata the field re-renders from', function (): void {
    [$store] = temporaryUploadsRig();
    $png = fixturePngBytes();

    $upload = $store->store(uploadedFixtureFile('holiday photo.png', $png), 'data.cover');

    expect($upload)->toBeInstanceOf(TemporaryUploadedFile::class)
        ->and($upload->getClientOriginalName())->toBe('holiday photo.png')
        ->and($upload->getClientOriginalExtension())->toBe('png')
        ->and($upload->getMimeType())->toBe('image/png')
        ->and($upload->getSize())->toBe(mb_strlen($png, '8bit'))
        ->and($upload->getDiskName())->toBe('tmp.storage')
        ->and($upload->getFilename())->toMatch('/^[0-9a-f]{40}\.png$/')
        ->and($upload->getPath())->toBe('sylament-tmp/' . $upload->getFilename())
        ->and($upload->exists())->toBeTrue();
});

it('serialises and stringifies to the token, so raw state holds one string', function (): void {
    [$store] = temporaryUploadsRig();
    $upload = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover');

    expect($upload->jsonSerialize())->toBe($upload->getToken())
        ->and((string) $upload)->toBe($upload->getToken())
        ->and(json_encode(['cover' => $upload]))->toBe(json_encode(['cover' => $upload->getToken()]));
});

it('reads its own bytes back as a stream', function (): void {
    [$store] = temporaryUploadsRig();
    $png = fixturePngBytes();
    $upload = $store->store(uploadedFixtureFile('photo.png', $png), 'data.cover');

    expect(stream_get_contents($upload->readStream()))->toBe($png);
});

it('reports a deleted file as gone rather than throwing', function (): void {
    [$store] = temporaryUploadsRig();
    $upload = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover');
    $store->getDisk()->delete($upload->getPath());

    expect($upload->exists())->toBeFalse();
});

it('deletes the bytes and the sidecar together, and swallows a second delete', function (): void {
    [$store] = temporaryUploadsRig();
    $upload = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover');

    $upload->delete();

    expect($store->getDisk()->fileExists($upload->getPath()))->toBeFalse()
        ->and($store->getDisk()->fileExists($upload->getPath() . '.json'))->toBeFalse()
        ->and($store->resolve($upload->getToken(), 'data.cover'))->toBeNull();

    // The 24-hour sweep races every removal, so a second delete is a no-op, never a failure.
    $upload->delete();
});
