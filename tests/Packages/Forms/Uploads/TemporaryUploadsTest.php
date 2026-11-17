<?php

declare(strict_types=1);

use League\Flysystem\FilesystemOperator;
use Sylament\Forms\Uploads\TemporaryUploadedFile;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Tests\Fixtures\Forms\FileUploadFixtures;
use Sylament\Tests\Infrastructure\WorkerDirectory;

beforeEach(function (): void {
    $this->registry = $this::getContainer()->get(FilesystemRegistry::class);
    $this->disk = $this->registry->get('primary.storage');
    $this->store = new TemporaryUploads($this->registry, 'test-secret');
});

describe('store()', function (): void {
    it('writes the bytes under a random name keeping the client extension, beside a sidecar of the original name, the sniffed type and the size', function (): void {
        $file = $this->store->store(FileUploadFixtures::file('pixel.png', 'Photo.JPG', 'image/jpeg'), 'data.photo');

        expect($file->getToken())->toMatch('/^sylament-file:[0-9a-f]{32}:[0-9a-f]{40}\.jpg$/')
            ->and($file->getPath())->toMatch('#^sylament-tmp/[0-9a-f]{40}\.jpg$#')
            ->and($this->disk->read($file->getPath()))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')))
            ->and(json_decode($this->disk->read($file->getPath() . '.json'), true))->toBe(['name' => 'Photo.JPG', 'type' => 'image/png', 'size' => 90]);
    });

    it('names the stored file after the client extension stripped to letters and digits, lower-cased and cut to 16 characters, or bin', function (string $clientName, string $extension): void {
        $file = $this->store->store(FileUploadFixtures::file('note.txt', $clientName), 'data.note');

        expect($file->getFilename())->toMatch('/^[0-9a-f]{40}\.' . preg_quote($extension, '/') . '$/');
    })->with([
        'punctuation and capitals' => ['note.T-X_T', 'txt'],
        'an extension over 16 characters' => ['note.' . str_repeat('ab', 10), str_repeat('ab', 8)],
        'an extension of zero' => ['note.0', '0'],
        'an extension of punctuation only' => ['note.-_', 'bin'],
        'no extension' => ['note', 'bin'],
    ]);

    it('holds its uploads on the storage it names, and on the default storage when it names none', function (): void {
        $elsewhere = new TemporaryUploads($this->registry, 'test-secret', 'secondary.storage', 'uploads');

        $held = $elsewhere->store(FileUploadFixtures::file('note.txt'), 'data.note');
        $default = $this->store->store(FileUploadFixtures::file('note.txt'), 'data.note');

        expect($held->getPath())->toStartWith('uploads/')
            ->and($this->registry->get('secondary.storage')->fileExists($held->getPath()))->toBeTrue()
            ->and($this->disk->fileExists($held->getPath()))->toBeFalse()
            ->and($this->disk->fileExists($default->getPath()))->toBeTrue();
    });
});

describe('resolve()', function (): void {
    it('resolves a token under the state path it was minted for, in any store on the same secret', function (): void {
        $stored = $this->store->store(FileUploadFixtures::file('pixel.png', 'Photo.png'), 'data.photo');

        $resolved = new TemporaryUploads($this->registry, 'test-secret')->resolve($stored->getToken(), 'data.photo');

        expect($resolved)->toBeInstanceOf(TemporaryUploadedFile::class)
            ->and($resolved->getFilename())->toBe($stored->getFilename())
            ->and($resolved->getClientOriginalName())->toBe('Photo.png');
    });

    it('answers null for a value the browser could forge or a file that is gone', function (Closure $forge): void {
        $stored = $this->store->store(FileUploadFixtures::file('pixel.png'), 'data.photo');

        [$token, $statePath] = $forge($stored, $this->disk);

        expect($this->store->resolve($token, $statePath))->toBeNull();
    })->with([
        'a stored path' => [fn (TemporaryUploadedFile $file): array => ['sylament-tmp/' . $file->getFilename(), 'data.photo']],
        'a prefixed string that was never minted' => [fn (): array => ['sylament-file:junk', 'data.photo']],
        'a token for another state path' => [fn (TemporaryUploadedFile $file): array => [$file->getToken(), 'data.other']],
        'a token whose signature was rewritten' => [fn (TemporaryUploadedFile $file): array => [TemporaryUploads::TOKEN_PREFIX . str_repeat('0', 32) . ':' . $file->getFilename(), 'data.photo']],
        'a signed name outside the minted shape' => [function (TemporaryUploadedFile $file, FilesystemOperator $disk): array {
            $disk->write('secret.env', 'APP_SECRET=1');
            $disk->write('secret.env.json', json_encode(['name' => 'secret.env', 'type' => 'text/plain', 'size' => 12], JSON_THROW_ON_ERROR));

            return [TemporaryUploads::TOKEN_PREFIX . mb_substr(hash_hmac('sha256', "../secret.env\0data.photo", 'test-secret'), 0, 32) . ':../secret.env', 'data.photo'];
        }],
        'deleted bytes' => [function (TemporaryUploadedFile $file, FilesystemOperator $disk): array {
            $disk->delete($file->getPath());

            return [$file->getToken(), 'data.photo'];
        }],
        'a deleted sidecar' => [function (TemporaryUploadedFile $file, FilesystemOperator $disk): array {
            $disk->delete($file->getPath() . '.json');

            return [$file->getToken(), 'data.photo'];
        }],
        'a sidecar that is not JSON' => [function (TemporaryUploadedFile $file, FilesystemOperator $disk): array {
            $disk->write($file->getPath() . '.json', '{');

            return [$file->getToken(), 'data.photo'];
        }],
        'a sidecar of the wrong shape' => [function (TemporaryUploadedFile $file, FilesystemOperator $disk): array {
            $disk->write($file->getPath() . '.json', json_encode(['name' => 'pixel.png', 'type' => 'image/png', 'size' => '90'], JSON_THROW_ON_ERROR));

            return [$file->getToken(), 'data.photo'];
        }],
    ]);
});

describe('isToken()', function (): void {
    it('tells a prefixed string from anything else, leaving whether it is valid to resolve()', function (): void {
        $token = $this->store->store(FileUploadFixtures::file('note.txt'), 'data.note')->getToken();

        expect(TemporaryUploads::isToken($token))->toBeTrue()
            ->and(TemporaryUploads::isToken('sylament-file:junk'))->toBeTrue()
            ->and($this->store->resolve('sylament-file:junk', 'data.note'))->toBeNull()
            ->and(TemporaryUploads::isToken('docs/note.txt'))->toBeFalse()
            ->and(TemporaryUploads::isToken(null))->toBeFalse()
            ->and(TemporaryUploads::isToken([$token]))->toBeFalse();
    });
});

describe('prune()', function (): void {
    beforeEach(function (): void {
        $this->age = function (string $path, int $hours): void {
            touch(WorkerDirectory::path() . '/storage/primary/' . $path, time() - $hours * 3600);
        };
    });

    it('deletes what under its directory is older than a day, answers how many went, and keeps the rest', function (): void {
        foreach (['sylament-tmp/a.bin', 'sylament-tmp/nested/b.bin', 'sylament-tmp/c.bin', 'outside.bin'] as $path) {
            $this->disk->write($path, 'x');
        }

        ($this->age)('sylament-tmp/a.bin', 25);
        ($this->age)('sylament-tmp/nested/b.bin', 25);
        ($this->age)('sylament-tmp/c.bin', 23);
        ($this->age)('outside.bin', 25);

        expect($this->store->prune())->toBe(2)
            ->and($this->disk->fileExists('sylament-tmp/a.bin'))->toBeFalse()
            ->and($this->disk->fileExists('sylament-tmp/nested/b.bin'))->toBeFalse()
            ->and($this->disk->fileExists('sylament-tmp/c.bin'))->toBeTrue()
            ->and($this->disk->fileExists('outside.bin'))->toBeTrue();
    });

    it('sweeps on every upload while cleanup is on, and never while it is off', function (bool $cleanup, bool $swept): void {
        $this->disk->write('sylament-tmp/stale.bin', 'x');
        ($this->age)('sylament-tmp/stale.bin', 25);

        new TemporaryUploads($this->registry, 'test-secret', cleanup: $cleanup)->store(FileUploadFixtures::file('note.txt'), 'data.note');

        expect($this->disk->fileExists('sylament-tmp/stale.bin'))->toBe(!$swept);
    })->with([
        'cleanup on' => [true, true],
        'cleanup off' => [false, false],
    ]);
});

describe('construction', function (): void {
    it('refuses a directory that normalises to the storage root, and takes one of spaces', function (): void {
        foreach (['', '/', '.'] as $directory) {
            expect(fn (): TemporaryUploads => new TemporaryUploads($this->registry, 'test-secret', directory: $directory))
                ->toThrow(InvalidArgumentException::class, 'cannot normalise to the storage root');
        }

        expect(new TemporaryUploads($this->registry, 'test-secret', directory: '   '))->toBeInstanceOf(TemporaryUploads::class);
    });
});
