<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Uploads;

use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A one-storage registry over a throwaway local directory, plus the directory itself so a
 * test can reach the bytes behind flysystem's back — `touch()` is how the sweep's clock moves.
 *
 * @return array{TemporaryUploads, string}
 */
function temporaryUploadsRig(bool $cleanup = true, string $directory = 'sylament-tmp'): array
{
    $root = sys_get_temp_dir() . '/sylament-uploads-' . bin2hex(random_bytes(6));
    $disk = new Filesystem(new LocalFilesystemAdapter($root));
    $registry = new FilesystemRegistry(
        new ServiceLocator(['tmp.storage' => static fn (): FilesystemOperator => $disk]),
        'tmp.storage',
    );

    return [new TemporaryUploads($registry, 'secret', null, $directory, $cleanup), $root];
}

function uploadedFixtureFile(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'sylament-upload-');

    if ($path === false) {
        throw new RuntimeException('Could not create a temporary file for the fixture upload.');
    }

    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

function fixturePngBytes(): string
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

it('stores the bytes and a sidecar under a random name and returns a signed token', function (): void {
    [$store] = temporaryUploadsRig();
    $png = fixturePngBytes();

    $upload = $store->store(uploadedFixtureFile('photo.png', $png), 'data.cover');

    expect($upload->getToken())->toMatch('/^sylament-file:[0-9a-f]{32}:[0-9a-f]{40}\.png$/')
        ->and($upload->getFilename())->toMatch('/^[0-9a-f]{40}\.png$/')
        ->and($upload->getPath())->toBe('sylament-tmp/' . $upload->getFilename())
        ->and($store->getDisk()->read($upload->getPath()))->toBe($png)
        ->and(json_decode($store->getDisk()->read($upload->getPath() . '.json'), true))->toBe([
            'name' => 'photo.png',
            'type' => 'image/png',
            'size' => mb_strlen($png, '8bit'),
        ]);
});

it('sniffs the mime type rather than trusting the browser', function (): void {
    [$store] = temporaryUploadsRig();

    // The client claim is `image/png`; the bytes are text. The sidecar must record the sniff.
    $file = new UploadedFile(uploadedFixtureFile('notes.txt', 'hello')->getPathname(), 'notes.txt', 'image/png', null, true);
    $upload = $store->store($file, 'data.cover');

    expect($upload->getMimeType())->toBe('text/plain');
});

it('resolves its own token for the same state path', function (): void {
    [$store] = temporaryUploadsRig();
    $png = fixturePngBytes();
    $token = $store->store(uploadedFixtureFile('photo.png', $png), 'data.cover')->getToken();

    $resolved = $store->resolve($token, 'data.cover');

    expect($resolved)->not->toBeNull()
        ->and($resolved->getToken())->toBe($token)
        ->and($resolved->getClientOriginalName())->toBe('photo.png')
        ->and($resolved->getClientOriginalExtension())->toBe('png')
        ->and($resolved->getMimeType())->toBe('image/png')
        ->and($resolved->getSize())->toBe(mb_strlen($png, '8bit'))
        ->and($resolved->getDiskName())->toBe('tmp.storage')
        ->and($resolved->exists())->toBeTrue()
        ->and(stream_get_contents($resolved->readStream()))->toBe($png);
});

it('refuses the token for a different state path', function (): void {
    [$store] = temporaryUploadsRig();
    $token = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover')->getToken();

    expect($store->resolve($token, 'data.other'))->toBeNull();
});

it('refuses a tampered signature, a swapped filename and a foreign prefix', function (): void {
    [$store] = temporaryUploadsRig();
    $token = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover')->getToken();
    [$signature, $filename] = explode(':', mb_substr($token, mb_strlen(TemporaryUploads::TOKEN_PREFIX)), 2);

    expect($store->resolve(TemporaryUploads::TOKEN_PREFIX . strrev($signature) . ':' . $filename, 'data.cover'))->toBeNull()
        ->and($store->resolve(TemporaryUploads::TOKEN_PREFIX . $signature . ':' . str_repeat('a', 40) . '.png', 'data.cover'))->toBeNull()
        ->and($store->resolve('sylament-file-x:' . $signature . ':' . $filename, 'data.cover'))->toBeNull()
        ->and($store->resolve('articles/covers/photo.png', 'data.cover'))->toBeNull();
});

it('refuses a filename outside the minted shape even under a valid signature', function (): void {
    // The signature alone would pass for any string, so the shape check is what stops a token
    // whose "filename" is a traversal or a sidecar name from naming an arbitrary path.
    [$store] = temporaryUploadsRig();
    $token = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover')->getToken();
    $filename = explode(':', mb_substr($token, mb_strlen(TemporaryUploads::TOKEN_PREFIX)), 2)[1];

    $sign = static fn (string $name): string => mb_substr(hash_hmac('sha256', $name . "\0" . 'data.cover', 'secret'), 0, 32);

    expect($store->resolve(TemporaryUploads::TOKEN_PREFIX . $sign('../secret.env') . ':../secret.env', 'data.cover'))->toBeNull()
        ->and($store->resolve(TemporaryUploads::TOKEN_PREFIX . $sign($filename . '.json') . ':' . $filename . '.json', 'data.cover'))->toBeNull();
});

it('resolves to null when the sidecar or the bytes are gone', function (): void {
    [$store] = temporaryUploadsRig();
    $upload = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover');
    $store->getDisk()->delete($upload->getPath());

    expect($store->resolve($upload->getToken(), 'data.cover'))->toBeNull();

    $second = $store->store(uploadedFixtureFile('photo.png', fixturePngBytes()), 'data.cover');
    $store->getDisk()->delete($second->getPath() . '.json');

    expect($store->resolve($second->getToken(), 'data.cover'))->toBeNull();
});

it('sanitises the extension and falls back to bin', function (string $name, string $extension): void {
    [$store] = temporaryUploadsRig();

    expect($store->store(uploadedFixtureFile($name, 'x'), 'data.cover')->getFilename())
        ->toEndWith('.' . $extension);
})->with([
    'the last segment wins' => ['evil.PHP;.txt', 'txt'],
    'lowercased' => ['photo.PNG', 'png'],
    'punctuation stripped' => ['photo.p-n g', 'png'],
    'no extension' => ['noext', 'bin'],
    'nothing survives the strip' => ['photo.___', 'bin'],
    'a falsy extension is still an extension' => ['photo.0', '0'],
    'capped at sixteen' => ['photo.' . 'abcdefghijklmnopqrstuvwxyz', 'abcdefghijklmnop'],
]);

it('prunes files older than 24 hours and leaves younger ones', function (): void {
    [$store, $root] = temporaryUploadsRig(cleanup: false);
    $old = $store->store(uploadedFixtureFile('old.png', fixturePngBytes()), 'data.cover');
    $young = $store->store(uploadedFixtureFile('young.png', fixturePngBytes()), 'data.cover');
    touch($root . '/' . $old->getPath(), time() - 90000);

    expect($store->prune())->toBe(1)
        ->and($store->getDisk()->fileExists($old->getPath()))->toBeFalse()
        ->and($store->getDisk()->fileExists($old->getPath() . '.json'))->toBeTrue()
        ->and($store->getDisk()->fileExists($young->getPath()))->toBeTrue()
        ->and($store->getDisk()->fileExists($young->getPath() . '.json'))->toBeTrue();
});

it('sweeps on a successful store when cleanup is on, and not when it is off', function (): void {
    [$sweeping, $sweepingRoot] = temporaryUploadsRig();
    $stale = $sweeping->store(uploadedFixtureFile('stale.png', fixturePngBytes()), 'data.cover');
    touch($sweepingRoot . '/' . $stale->getPath(), time() - 90000);
    $sweeping->store(uploadedFixtureFile('fresh.png', fixturePngBytes()), 'data.cover');

    expect($sweeping->getDisk()->fileExists($stale->getPath()))->toBeFalse();

    [$quiet, $quietRoot] = temporaryUploadsRig(cleanup: false);
    $kept = $quiet->store(uploadedFixtureFile('stale.png', fixturePngBytes()), 'data.cover');
    touch($quietRoot . '/' . $kept->getPath(), time() - 90000);
    $quiet->store(uploadedFixtureFile('fresh.png', fixturePngBytes()), 'data.cover');

    expect($quiet->getDisk()->fileExists($kept->getPath()))->toBeTrue();
});

it('returns zero from prune when the temp directory has never been written to', function (): void {
    // store() prunes on every success, so an unlistable directory must not become a 500.
    [$store] = temporaryUploadsRig();

    expect($store->prune())->toBe(0);
});

it('recognises tokens', function (): void {
    expect(TemporaryUploads::isToken('sylament-file:x:y'))->toBeTrue()
        ->and(TemporaryUploads::isToken('a/b.png'))->toBeFalse()
        ->and(TemporaryUploads::isToken(3))->toBeFalse()
        ->and(TemporaryUploads::isToken(null))->toBeFalse();
});

it('refuses a directory that normalises to the storage root', function (string $directory): void {
    expect(fn (): array => temporaryUploadsRig(directory: $directory))
        ->toThrow(InvalidArgumentException::class, 'storage root');
})->with([
    'empty' => [''],
    'slash' => ['/'],
    'dot' => ['.'],
]);

it('names the disk and the normalised directory', function (): void {
    // `WhitespacePathNormalizer` rejects control characters and collapses the separators; it does
    // not trim spaces, so a directory named with them survives.
    [$store] = temporaryUploadsRig(directory: '/sylament tmp//nested/');

    expect($store->getDiskName())->toBe('tmp.storage')
        ->and($store->getDirectory())->toBe('sylament tmp/nested')
        ->and($store->getDisk())->toBeInstanceOf(FilesystemOperator::class);
});
