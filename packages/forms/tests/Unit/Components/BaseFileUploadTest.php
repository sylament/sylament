<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use League\Flysystem\FilesystemOperator;
use LogicException;
use ReflectionMethod;
use Sylament\Forms\Components\BaseFileUpload;
use Sylament\Forms\Tests\Fixtures\Components\FixtureFileUpload;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Gallery;
use Sylament\Forms\Tests\Unit\Uploads\TemporaryUploadsRig;
use Sylament\Forms\Uploads\TemporaryUploadedFile;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Forms\Validation\MinFileSize;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\FileUploadStateCast;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

afterEach(function (): void {
    TemporaryUploadsRig::cleanUp();
});

/**
 * Wire a file upload under a `data`-bag host so its state path resolves, optionally binding the
 * root schema to a record. The fixture subclass is what proves the whole lifecycle sits on
 * `BaseFileUpload` rather than on the FilePond-flavoured `FileUpload`.
 *
 * @param array<string, mixed>            $data
 * @param object|array<string, mixed>|null $record
 *
 * @return array{FixtureFileUpload, FieldRenderHost}
 */
function wireFileUpload(FixtureFileUpload $upload, array $data = [], object|array|null $record = null): array
{
    $host = new FieldRenderHost();
    $host->data = $data;

    $schema = Schema::make($host)->statePath('data');

    if ($record !== null) {
        $schema->record($record);
    }

    $schema->components([$upload])->getComponents();

    return [$upload, $host];
}

/**
 * A registry naming one default disk and holding no storages: enough for every getter that only
 * asks the registry what the default is called.
 */
function fileUploadRegistry(string $defaultDiskName = 'default.storage', ?FilesystemOperator $disk = null): FilesystemRegistry
{
    return new FilesystemRegistry(
        new ServiceLocator($disk instanceof FilesystemOperator ? [$defaultDiskName => static fn (): FilesystemOperator => $disk] : []),
        $defaultDiskName,
    );
}

/**
 * A real held upload over the shared temporary-upload rig, which is where the throwaway local
 * storage and the fixture `UploadedFile` come from.
 *
 * @return array{TemporaryUploads, TemporaryUploadedFile}
 */
function heldUpload(string $name = 'holiday photo.png'): array
{
    [$store] = TemporaryUploadsRig::make();

    return [$store, $store->store(TemporaryUploadsRig::uploadedFile($name, TemporaryUploadsRig::pngBytes()), 'data.cover')];
}

it('validates the file types and sizes against the uploaded file, not the saved collection', function (): void {
    [$upload] = wireFileUpload(
        FixtureFileUpload::make('cover')
            ->acceptedFileTypes(['image/*'])
            ->maxSize(512)
            ->minSize(16),
    );

    $constraints = $upload->getUploadedFileConstraints();

    $mimeTypes = array_values(array_filter($constraints, fn (object $constraint): bool => $constraint instanceof File && $constraint->mimeTypes !== []));
    $maxSizes = array_values(array_filter($constraints, fn (object $constraint): bool => $constraint instanceof File && $constraint->maxSize !== null));
    $minSizes = array_values(array_filter($constraints, fn (object $constraint): bool => $constraint instanceof MinFileSize));

    expect($mimeTypes)->toHaveCount(1)
        ->and($mimeTypes[0]->mimeTypes)->toBe(['image/*'])
        ->and($maxSizes)->toHaveCount(1)
        ->and($maxSizes[0]->maxSize)->toBe(512 * 1024)
        ->and($minSizes)->toHaveCount(1)
        ->and($minSizes[0]->kilobytes)->toBe(16);
});

it('turns one aspect ratio into an Image with both bounds and several into an AtLeastOneOf', function (): void {
    [$single] = wireFileUpload(FixtureFileUpload::make('cover')->imageAspectRatio('1:1'));

    $images = array_values(array_filter($single->getUploadedFileConstraints(), fn (object $constraint): bool => $constraint instanceof Image));

    expect($images)->toHaveCount(1)
        ->and($images[0]->minRatio)->toBe(1.0)
        ->and($images[0]->maxRatio)->toBe(1.0);

    [$several] = wireFileUpload(FixtureFileUpload::make('banner')->imageAspectRatio(['16:9', '4:3']));

    $alternatives = array_values(array_filter($several->getUploadedFileConstraints(), fn (object $constraint): bool => $constraint instanceof AtLeastOneOf));

    expect($alternatives)->toHaveCount(1)
        ->and($alternatives[0]->constraints)->toHaveCount(2)
        ->and($alternatives[0]->constraints[0])->toBeInstanceOf(Image::class)
        ->and($alternatives[0]->constraints[1])->toBeInstanceOf(Image::class);
});

it('routes a user File constraint to the upload set and every other one to the collection set', function (): void {
    [$upload] = wireFileUpload(
        FixtureFileUpload::make('cover')
            ->constraint(new File(mimeTypes: ['application/pdf']))
            ->constraint(new Email()),
    );

    expect(array_filter($upload->getUploadedFileConstraints(), fn (object $constraint): bool => $constraint instanceof File))->toHaveCount(1)
        ->and(array_filter($upload->getUploadedFileConstraints(), fn (object $constraint): bool => $constraint instanceof Email))->toHaveCount(0)
        ->and(array_filter($upload->getValidationConstraints(), fn (object $constraint): bool => $constraint instanceof File))->toHaveCount(0)
        ->and(array_filter($upload->getValidationConstraints(), fn (object $constraint): bool => $constraint instanceof Email))->toHaveCount(1);
});

it('emits the required constraint exactly once, through the parent hook', function (): void {
    // The regression the override's placement exists for: overriding the public getter instead of
    // `buildValidationConstraints()` would emit `NotBlank` here twice and skip the message layer.
    [$upload] = wireFileUpload(FixtureFileUpload::make('cover')->required());

    expect(array_filter($upload->getValidationConstraints(), fn (object $constraint): bool => $constraint instanceof NotBlank))->toHaveCount(1);
});

it('counts the collection between minFiles and maxFiles', function (): void {
    [$upload] = wireFileUpload(FixtureFileUpload::make('gallery')->multiple()->minFiles(1)->maxFiles(3));

    $counts = array_values(array_filter($upload->getValidationConstraints(), fn (object $constraint): bool => $constraint instanceof Count));

    expect($counts)->toHaveCount(1)
        ->and($counts[0]->min)->toBe(1)
        ->and($counts[0]->max)->toBe(3);
});

it('adds the path-authorisation callback only under preventFilePathTampering()', function (): void {
    [$open] = wireFileUpload(FixtureFileUpload::make('cover'));
    [$gated] = wireFileUpload(FixtureFileUpload::make('gated')->preventFilePathTampering());

    expect(array_filter($open->getValidationConstraints(), fn (object $constraint): bool => $constraint instanceof Callback))->toHaveCount(0)
        ->and(array_filter($gated->getValidationConstraints(), fn (object $constraint): bool => $constraint instanceof Callback))->toHaveCount(1);
});

it('reads the record it is bound to for the paths a tampering gate authorises', function (): void {
    $record = new Gallery();
    $record->coverImage = 'covers/a.png';
    $record->gallery = ['covers/b.png', 42, ''];

    [$cover] = wireFileUpload(FixtureFileUpload::make('coverImage'), record: $record);
    [$gallery] = wireFileUpload(FixtureFileUpload::make('gallery')->multiple(), record: $record);
    [$missing] = wireFileUpload(FixtureFileUpload::make('nothingHere'), record: $record);
    [$uninitialized] = wireFileUpload(FixtureFileUpload::make('uninitializedCover'), record: $record);
    [$arrayRecord] = wireFileUpload(FixtureFileUpload::make('cover'), record: ['cover' => 'covers/c.png']);
    [$noRecord] = wireFileUpload(FixtureFileUpload::make('cover'));

    expect($cover->getOriginalFilePaths())->toBe(['covers/a.png'])
        ->and($gallery->getOriginalFilePaths())->toBe(['covers/b.png'])
        ->and($missing->getOriginalFilePaths())->toBe([])
        ->and($uninitialized->getOriginalFilePaths())->toBe([])
        ->and($arrayRecord->getOriginalFilePaths())->toBe(['covers/c.png'])
        ->and($noRecord->getOriginalFilePaths())->toBe([]);
});

it('authorises a path the record holds, refuses any other, and defers to allowFilePathUsing', function (): void {
    $record = new Gallery();
    $record->coverImage = 'covers/a.png';

    [$gated] = wireFileUpload(FixtureFileUpload::make('coverImage')->preventFilePathTampering(), record: $record);

    expect($gated->isFilePathAuthorized('covers/a.png'))->toBeTrue()
        ->and($gated->isFilePathAuthorized('secret/other.png'))->toBeFalse();

    [$allowing] = wireFileUpload(
        FixtureFileUpload::make('coverImage')->preventFilePathTampering(
            allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'public/'),
        ),
        record: $record,
    );

    expect($allowing->isFilePathAuthorized('public/other.png'))->toBeTrue()
        ->and($allowing->isFilePathAuthorized('secret/other.png'))->toBeFalse();
});

it('drops a hydrated path the disk does not have and keeps one it does', function (): void {
    [$store] = TemporaryUploadsRig::make();
    $disk = $store->getDisk();
    $disk->write('covers/a.png', 'bytes');

    [$upload, $host] = wireFileUpload(
        FixtureFileUpload::make('cover')->multiple()->disk('fixture.storage'),
        ['cover' => ['one' => 'covers/a.png', 'two' => 'covers/gone.png', 'three' => '']],
    );

    $upload->setFilesystemRegistry(fileUploadRegistry('fixture.storage', $disk));
    $upload->hydrateFiles();

    expect($host->data['cover'])->toBe(['one' => 'covers/a.png']);
});

it('keeps every non-blank path when it is told not to fetch file information', function (): void {
    [$upload, $host] = wireFileUpload(
        FixtureFileUpload::make('cover')->multiple()->fetchFileInformation(false),
        ['cover' => ['one' => 'covers/gone.png', 'two' => '']],
    );

    $upload->hydrateFiles();

    expect($host->data['cover'])->toBe(['one' => 'covers/gone.png']);
});

it('names the registry default disk when the field names none', function (): void {
    [$upload] = wireFileUpload(FixtureFileUpload::make('cover'));
    $upload->setFilesystemRegistry(fileUploadRegistry());

    expect($upload->getDiskName())->toBe('default.storage');

    [$named] = wireFileUpload(FixtureFileUpload::make('cover')->disk('other.storage'));
    $named->setFilesystemRegistry(fileUploadRegistry());

    expect($named->getDiskName())->toBe('other.storage');
});

it('refuses to upload when it was built outside the container', function (): void {
    [$upload] = wireFileUpload(FixtureFileUpload::make('cover'));

    expect(fn (): TemporaryUploads => $upload->getTemporaryUploads())->toThrow(LogicException::class)
        ->and(fn (): FilesystemRegistry => $upload->getFilesystemRegistry())->toThrow(LogicException::class);
});

it('names a stored file by ulid, or by the client name under preserveFilenames()', function (): void {
    [$generated] = wireFileUpload(FixtureFileUpload::make('cover'));
    [$preserved] = wireFileUpload(FixtureFileUpload::make('cover')->preserveFilenames());

    [, $file] = heldUpload();

    expect($generated->getUploadedFileNameForStorage($file))->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}\.png$/')
        ->and($preserved->getUploadedFileNameForStorage($file))->toBe('holiday photo.png');
});

it('leaves visibility to the storage until the field names one', function (): void {
    [$default] = wireFileUpload(FixtureFileUpload::make('cover'));
    [$private] = wireFileUpload(FixtureFileUpload::make('cover')->visibility('private'));

    expect($default->getVisibility())->toBeNull()
        ->and($private->getVisibility())->toBe('private');
});

it('appends the file upload state cast, keyed by multiplicity', function (): void {
    [$single] = wireFileUpload(FixtureFileUpload::make('cover'));
    [$multiple] = wireFileUpload(FixtureFileUpload::make('gallery')->multiple());

    $singleCast = array_last($single->getDefaultStateCasts());
    $multipleCast = array_last($multiple->getDefaultStateCasts());

    expect($singleCast)->toBeInstanceOf(FileUploadStateCast::class)
        ->and($multipleCast)->toBeInstanceOf(FileUploadStateCast::class)
        ->and($singleCast->get(['a' => 'covers/a.png']))->toBe('covers/a.png')
        ->and($multipleCast->get(['a' => 'covers/a.png']))->toBe(['covers/a.png']);
});

it('reads the PHP upload ceiling as an int', function (): void {
    // `UploadedFile::getMaxFilesize()` is declared `int|float` — it returns a float past
    // `PHP_INT_MAX` — so the narrowing is what keeps the `int` return type true.
    [$upload] = wireFileUpload(FixtureFileUpload::make('cover'));

    expect($upload->getMaxUploadSize())
        ->toBeInt()
        ->toBe((int) min(UploadedFile::getMaxFilesize(), PHP_INT_MAX));
});

it('declares the whole lifecycle on the base class', function (): void {
    // Every method the field surface promises is `BaseFileUpload`'s, so a second subclass — or a
    // consumer's own — inherits the lifecycle without touching `FileUpload`.
    foreach ([
        'acceptUploadedFile', 'hydrateFiles', 'saveUploadedFiles', 'saveUploadedFile',
        'getUploadedFiles', 'getUploadedFile', 'getUploadedFileConstraints', 'getMaxUploadSize',
        'removeUploadedFile', 'deleteUploadedFile', 'reorderUploadedFiles', 'getOriginalFilePaths',
    ] as $method) {
        expect(new ReflectionMethod(FixtureFileUpload::class, $method)->getDeclaringClass()->getName())
            ->toBe(BaseFileUpload::class);
    }
});

it('keeps the stored file names beside the state when storeFileNamesIn() names a path', function (): void {
    [$upload, $host] = wireFileUpload(FixtureFileUpload::make('cover')->storeFileNamesIn('coverName'));

    $upload->storeFileName('covers/a.png', 'holiday.png');

    expect($host->data['coverName'])->toBe('holiday.png')
        ->and($upload->getStoredFileNames())->toBe('holiday.png')
        ->and($upload->getStateToDehydrate('covers/a.png'))->toBe([
            'data.cover' => 'covers/a.png',
            'data.coverName' => 'holiday.png',
        ]);

    $upload->removeStoredFileName('covers/a.png');

    expect($upload->getStoredFileNames())->toBeNull();
});

it('keys the stored file names by path when multiple', function (): void {
    [$upload] = wireFileUpload(FixtureFileUpload::make('gallery')->multiple()->storeFileNamesIn('galleryNames'));

    $upload->storeFileName('covers/a.png', 'a.png');
    $upload->storeFileName('covers/b.png', 'b.png');

    expect($upload->getStoredFileNames())->toBe(['covers/a.png' => 'a.png', 'covers/b.png' => 'b.png']);

    $upload->removeStoredFileName('covers/a.png');

    expect($upload->getStoredFileNames())->toBe(['covers/b.png' => 'b.png']);
});

it('refuses an upload while disabled', function (): void {
    [$upload] = wireFileUpload(FixtureFileUpload::make('cover')->disabled());

    expect($upload->acceptUploadedFile('abc', TemporaryUploadsRig::uploadedFile('a.png', 'bytes')))->toBeNull();
});

it('answers the error message rather than storing a file the browser could not deliver', function (): void {
    [$upload] = wireFileUpload(FixtureFileUpload::make('cover'));

    $file = new UploadedFile(
        TemporaryUploadsRig::uploadedFile('a.png', 'bytes')->getPathname(),
        'a.png',
        null,
        UPLOAD_ERR_INI_SIZE,
        true,
    );

    $result = $upload->acceptUploadedFile('abc', $file);

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('a.png');
});

it('answers the first violation and writes nothing when a constraint refuses the file', function (): void {
    [$upload, $host] = wireFileUpload(FixtureFileUpload::make('cover')->acceptedFileTypes(['image/*']));

    $result = $upload->acceptUploadedFile('abc', TemporaryUploadsRig::uploadedFile('notes.txt', 'hello'));

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toBeString()
        ->and($host->data)->toBe([]);
});
