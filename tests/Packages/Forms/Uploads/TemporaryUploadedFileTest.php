<?php

declare(strict_types=1);

use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Tests\Fixtures\Forms\FileUploadFixtures;

beforeEach(function (): void {
    $this->disk = $this::getContainer()->get(FilesystemRegistry::class)->get('primary.storage');
    $this->store = $this::getContainer()->get(TemporaryUploads::class);
    $this->file = $this->store->store(FileUploadFixtures::file('pixel.png', 'Photo.JPG', 'image/jpeg'), 'data.photo');
});

it('answers the metadata of the upload it was stored from, and the same once resolved from its sidecar', function (): void {
    $metadata = static fn ($file): array => [
        $file->getToken(),
        $file->getFilename(),
        $file->getPath(),
        $file->getDiskName(),
        $file->getClientOriginalName(),
        $file->getClientOriginalExtension(),
        $file->getMimeType(),
        $file->getSize(),
    ];

    expect($metadata($this->file))->toBe([
        $this->file->getToken(),
        $this->file->getFilename(),
        'sylament-tmp/' . $this->file->getFilename(),
        'primary.storage',
        'Photo.JPG',
        'JPG',
        'image/png',
        90,
    ])
        ->and($metadata($this->store->resolve($this->file->getToken(), 'data.photo')))->toBe($metadata($this->file));
});

it('reads, checks and deletes the file it holds, and shrugs at a second delete', function (): void {
    expect($this->file->exists())->toBeTrue()
        ->and(stream_get_contents($this->file->readStream()))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')));

    $this->file->delete();

    expect($this->file->exists())->toBeFalse()
        ->and($this->disk->fileExists($this->file->getPath() . '.json'))->toBeFalse()
        ->and($this->file->delete())->toBeNull();
});

it('serialises to its token in JSON and as a string, so raw state holds one string', function (): void {
    expect(json_encode(['k1' => $this->file], JSON_THROW_ON_ERROR))->toBe(json_encode(['k1' => $this->file->getToken()], JSON_THROW_ON_ERROR))
        ->and((string) $this->file)->toBe($this->file->getToken());
});
