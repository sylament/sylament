<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

use function Sylament\Forms\Tests\uploadStorageDir;

uses(InteractsWithLiveComponents::class);

/*
 * The whole upload lifecycle over a real live round-trip: the browser posts a file under
 * `sylament_upload_<fileKey>`, the schemas seam hands it to the field, the field validates it,
 * holds it as a signed token, and `save` resolves that token onto the field's storage.
 *
 * A validation failure is asserted the way `Components/FieldErrorsTest.php` documents:
 * `TestLiveComponent` forces `catchExceptions(false)`, so the `ValidationException` escapes the
 * action rather than becoming a 422 the harness could look for.
 */

/** A real 1×1 PNG, so the sniffed MIME type is `image/png` rather than a guess. */
function pngBytes(): string
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

function fixtureFile(string $name = 'holiday.png', ?string $contents = null): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'sylament-live-upload-');

    if ($path === false) {
        throw new RuntimeException('Could not create a temporary file for the fixture upload.');
    }

    file_put_contents($path, $contents ?? pngBytes());

    return new UploadedFile($path, $name, null, null, true);
}

function liveStorage(string $name = 'test.storage'): FilesystemOperator
{
    $storage = test()->getContainer()->get($name);

    expect($storage)->toBeInstanceOf(FilesystemOperator::class);

    return $storage;
}

/**
 * Post one file to a component through the schemas upload seam and answer the result the browser
 * would have received.
 *
 * @return array<string, mixed>|null
 */
function upload(TestLiveComponent $component, string $componentKey, string $fileKey, UploadedFile $file): ?array
{
    $component->call(
        'uploadSchemaComponentFile',
        ['componentKey' => $componentKey, 'fileKey' => $fileKey],
        ['sylament_upload_' . $fileKey => $file],
    );

    $event = $component->getDispatchedBrowserEvent($component->render(), 'sylament:schema-component-method-result');

    return $event['payload']['result'] ?? null;
}

/**
 * Run the host's own validation in process and hand the host back. There is no 422 to look for:
 * `TestLiveComponent` forces `catchExceptions(false)`, so a `ValidationException` escapes the
 * action before the response is stored and `component()` would answer the pre-submit object —
 * `Components/FieldErrorsTest.php` records the same thing.
 */
function validated(TestLiveComponent $component): InteractsWithSchemas
{
    $host = $component->component();

    expect($host)->toBeInstanceOf(InteractsWithSchemas::class);

    try {
        $host->getSchema()->validate();
    } catch (ValidationException) {
    }

    return $host;
}

/**
 * @return list<string>
 */
function temporaryFiles(): array
{
    $listing = liveStorage()->listContents('sylament-tmp', deep: true);

    return array_values(array_map(
        static fn (object $item): string => (string) $item->path(),
        array_filter(iterator_to_array($listing, false), static fn (object $item): bool => $item->isFile()),
    ));
}

it('drops a hydrated path the disk does not have and keeps one it does', function (): void {
    liveStorage()->write('covers/kept.png', pngBytes());

    $component = $this->createLiveComponent('FileUploadForm', [
        'seed' => ['cover' => ['covers/kept.png', 'covers/gone.png']],
    ]);

    expect(array_values($component->component()->data['cover']))->toBe(['covers/kept.png']);
});

it('holds an accepted upload as a signed token and answers its metadata', function (): void {
    $component = $this->createLiveComponent('FileUploadForm');

    $result = upload($component, 'schema.cover', 'abc-123', fixtureFile());

    expect($result)->toBe([
        'ok' => true,
        'fileKey' => 'abc-123',
        'name' => 'holiday.png',
        'size' => mb_strlen(pngBytes(), '8bit'),
        'type' => 'image/png',
    ]);

    $token = $component->component()->data['cover']['abc-123'];

    expect(TemporaryUploads::isToken($token))->toBeTrue()
        ->and(temporaryFiles())->toHaveCount(2);
});

it('refuses a file whose type the field does not accept, writing nothing', function (): void {
    $component = $this->createLiveComponent('FileUploadForm', ['acceptedFileTypes' => ['image/*']]);

    $result = upload($component, 'schema.cover', 'abc', fixtureFile('notes.txt', 'hello'));

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toBeString()
        ->and($component->component()->data['cover'])->toBe([])
        ->and(temporaryFiles())->toBe([]);
});

it('refuses a file over maxSize and one under minSize', function (): void {
    $tooBig = $this->createLiveComponent('FileUploadForm', ['maxSize' => 1]);

    expect(upload($tooBig, 'schema.cover', 'abc', fixtureFile('big.png', str_repeat('x', 2048)))['ok'])->toBeFalse();

    $tooSmall = $this->createLiveComponent('FileUploadForm', ['minSize' => 1]);
    $result = upload($tooSmall, 'schema.cover', 'abc', fixtureFile('tiny.png', ''));

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('Minimum size is 1 KiB');
});

it('stores the held file under the directory on save and clears the temporary one', function (): void {
    $component = $this->createLiveComponent('FileUploadForm');
    upload($component, 'schema.cover', 'abc', fixtureFile());

    $component->call('save');

    $saved = $component->component()->savedState['cover'];

    expect($saved)->toMatch('#^covers/[0-9A-HJKMNP-TV-Z]{26}\.png$#')
        ->and(liveStorage()->fileExists($saved))->toBeTrue()
        ->and(temporaryFiles())->toBe([])
        ->and($component->component()->data['cover'])->toBe(['abc' => $saved]);
});

it('keeps a multiple field in order and reorders it on request', function (): void {
    $component = $this->createLiveComponent('FileUploadForm', ['isReorderable' => true]);

    upload($component, 'schema.attachments', 'one', fixtureFile('one.png'));
    upload($component, 'schema.attachments', 'two', fixtureFile('two.png'));

    $component->call('save');

    $saved = $component->component()->savedState['attachments'];

    // The cast dehydrates a multiple field to a positional list, in state order.
    expect($saved)->toBeArray()->toHaveCount(2)
        ->and(array_keys($saved))->toBe([0, 1])
        ->and($saved[0])->toStartWith('attachments/')
        ->and($saved[1])->toStartWith('attachments/')
        ->and($saved[0])->not->toBe($saved[1]);

    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.attachments',
        'method' => 'reorderUploadedFiles',
        'arguments' => ['fileKeys' => ['two', 'one']],
    ]);

    expect(array_keys($component->component()->data['attachments']))->toBe(['two', 'one']);
});

it('names the stored file by the client name or by a caller generator', function (): void {
    $preserved = $this->createLiveComponent('FileUploadForm', ['shouldPreserveFilenames' => true]);
    upload($preserved, 'schema.cover', 'abc', fixtureFile());
    $preserved->call('save');

    expect($preserved->component()->savedState['cover'])->toBe('covers/holiday.png');

    $custom = $this->createLiveComponent('FileUploadForm', ['usesCustomFileName' => true]);
    upload($custom, 'schema.cover', 'abc', fixtureFile());
    $custom->call('save');

    expect($custom->component()->savedState['cover'])->toBe('covers/x.png');
});

it('dehydrates the original filenames beside the state when storeFileNamesIn() names a path', function (): void {
    $component = $this->createLiveComponent('FileUploadForm', ['fileNamesStatePath' => 'coverName']);
    upload($component, 'schema.cover', 'abc', fixtureFile());
    $component->call('save');

    expect($component->component()->savedState['coverName'])->toBe('holiday.png');
});

it('moves the bytes on the same storage and streams them across to another', function (): void {
    $moving = $this->createLiveComponent('FileUploadForm', ['shouldMoveFiles' => true]);
    upload($moving, 'schema.cover', 'abc', fixtureFile());
    $moving->call('save');

    expect(liveStorage()->fileExists($moving->component()->savedState['cover']))->toBeTrue()
        ->and(temporaryFiles())->toBe([]);

    $copying = $this->createLiveComponent('FileUploadForm', ['coverDisk' => 'other.storage']);
    upload($copying, 'schema.cover', 'abc', fixtureFile());
    $copying->call('save');

    // The temporary store is `test.storage`; the field's is not, so the bytes are streamed over.
    expect(liveStorage('other.storage')->fileExists($copying->component()->savedState['cover']))->toBeTrue()
        ->and(temporaryFiles())->toBe([]);
});

it('sets the visibility the field names on the stored file', function (): void {
    $component = $this->createLiveComponent('FileUploadForm', ['visibility' => 'private']);
    upload($component, 'schema.cover', 'abc', fixtureFile());
    $component->call('save');

    expect(liveStorage()->visibility($component->component()->savedState['cover']))->toBe('private');
});

it('deletes the temporary file when the browser removes it, and runs the delete hook on a stored path', function (): void {
    $component = $this->createLiveComponent('FileUploadForm');
    upload($component, 'schema.cover', 'abc', fixtureFile());

    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.cover',
        'method' => 'removeUploadedFile',
        'arguments' => ['fileKey' => 'abc'],
    ]);

    expect($component->component()->data['cover'])->toBe([])
        ->and(temporaryFiles())->toBe([]);

    liveStorage()->write('covers/kept.png', pngBytes());
    $stored = $this->createLiveComponent('FileUploadForm', ['seed' => ['cover' => 'covers/kept.png']]);

    $stored->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.cover',
        'method' => 'deleteUploadedFile',
        'arguments' => ['fileKey' => array_key_first($stored->component()->data['cover'])],
    ]);

    expect($stored->component()->deletedFiles)->toBe(['covers/kept.png'])
        ->and($stored->component()->data['cover'])->toBe([]);
});

it('refuses the upload and every mutator while disabled', function (): void {
    liveStorage()->write('covers/kept.png', pngBytes());

    $component = $this->createLiveComponent('FileUploadForm', [
        'isDisabled' => true,
        'seed' => ['cover' => 'covers/kept.png'],
    ]);

    expect(upload($component, 'schema.cover', 'abc', fixtureFile()))->toBeNull();

    $fileKey = array_key_first($component->component()->data['cover']);

    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.cover',
        'method' => 'removeUploadedFile',
        'arguments' => ['fileKey' => $fileKey],
    ]);

    expect($component->component()->data['cover'])->toHaveKey($fileKey);
});

it('fires the state hook on an upload and on a removal', function (): void {
    $component = $this->createLiveComponent('FileUploadForm');
    upload($component, 'schema.cover', 'abc', fixtureFile());

    expect($component->component()->stateUpdates)->toBe(1);

    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.cover',
        'method' => 'removeUploadedFile',
        'arguments' => ['fileKey' => 'abc'],
    ]);

    expect($component->component()->stateUpdates)->toBe(2);
});

it('counts the collection at save and requires a file exactly once', function (): void {
    $counted = validated($this->createLiveComponent('FileUploadForm', ['minFiles' => 1]));

    expect($counted->getSchema()->getComponentByStatePath('data.cover')->getErrorMessages())->toHaveCount(1);

    // Exactly one message: the override sits on `buildValidationConstraints()`, so the parent hook
    // is still the only thing that emits `NotBlank`.
    $required = validated($this->createLiveComponent('FileUploadForm', ['isRequired' => true]));

    expect($required->getSchema()->getComponentByStatePath('data.cover')->getErrorMessages())
        ->toBe(['This value should not be blank.']);
});

it('refuses a foreign path the client wrote into the bag, and never its own token', function (): void {
    liveStorage()->write('covers/original.png', pngBytes());

    $component = $this->createLiveComponent('FileUploadRecordForm', ['originalCover' => 'covers/original.png']);
    $component->set('data', ['coverImage' => ['k' => 'secret/other.png']]);

    // The gate refuses the tampered path in the mutator …
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.coverImage',
        'method' => 'removeUploadedFile',
        'arguments' => ['fileKey' => 'k'],
    ]);

    expect($component->component()->data['coverImage'])->toHaveKey('k');

    // … it renders as nothing, so the browser is never handed a link to it …
    $field = $component->component()->getSchema()->getComponentByStatePath('data.coverImage');

    expect($field->getUploadedFiles())->toBe(['k' => null]);

    // … and again at save, where the collection constraint surfaces it under the field.
    expect(validated($component)->getSchema()->getComponentByStatePath('data.coverImage')->getErrorMessages())
        ->toBe(['The submitted file path is not authorised.']);
});

it('lets the user remove the file they just uploaded even under the tampering gate', function (): void {
    // A freshly minted token is never among the record's original paths, so a gate that did not
    // exclude our own tokens would refuse the user's own upload and leak the temporary file.
    $component = $this->createLiveComponent('FileUploadRecordForm');

    upload($component, 'schema.coverImage', 'abc', fixtureFile());

    expect(temporaryFiles())->toHaveCount(2);

    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.coverImage',
        'method' => 'removeUploadedFile',
        'arguments' => ['fileKey' => 'abc'],
    ]);

    expect($component->component()->data['coverImage'])->toBe([])
        ->and(temporaryFiles())->toBe([]);
});

it('writes the stored path onto the record it is bound to', function (): void {
    $component = $this->createLiveComponent('FileUploadRecordForm', ['preventsFilePathTampering' => false]);

    upload($component, 'schema.coverImage', 'abc', fixtureFile());
    $component->call('save');

    expect($component->component()->savedCover)->toMatch('#^covers/[0-9A-HJKMNP-TV-Z]{26}\.png$#')
        ->and(is_dir(uploadStorageDir('test')))->toBeTrue();
});
