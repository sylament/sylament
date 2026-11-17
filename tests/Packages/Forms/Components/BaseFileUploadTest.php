<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Tests\Fixtures\Forms\FileUploadFixtures;
use Sylament\Tests\Fixtures\Forms\GalleryForm;
use Sylament\Tests\Fixtures\Forms\GuardedUploadForm;
use Sylament\Tests\Fixtures\Forms\RepeatedAttachmentForm;
use Sylament\Tests\Fixtures\Forms\SavingUploadForm;
use Sylament\Tests\Fixtures\Forms\UploadForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Symfony\Component\HttpFoundation\File\UploadedFile;

beforeEach(function (): void {
    $registry = $this::getContainer()->get(FilesystemRegistry::class);
    $this->primary = $registry->get('primary.storage');
    $this->secondary = $registry->get('secondary.storage');

    $this->upload = fn (Testable $tester, string $field, string $fileKey, UploadedFile $file): Testable => $tester->call(
        'uploadSchemaComponentFile',
        ['componentKey' => "form.{$field}", 'fileKey' => $fileKey],
        ["sylament_upload_{$fileKey}" => $file],
    );

    $this->callMethod = fn (Testable $tester, string $field, string $method, array $arguments): Testable => $tester->call(
        'callSchemaComponentMethod',
        ['componentKey' => "form.{$field}", 'method' => $method, 'arguments' => $arguments],
    );

    $this->result = fn (Testable $tester): mixed => $this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result')[0]['result'];

    $this->temporaryFiles = fn (): array => $this->primary->directoryExists('sylament-tmp')
        ? $this->primary->listContents('sylament-tmp')->map(static fn ($item): string => $item->path())->toArray()
        : [];

    $this->storedFiles = fn ($disk): array => $disk->listContents('', deep: true)
        ->filter(static fn ($item): bool => $item->isFile() && !str_starts_with($item->path(), 'sylament-tmp/'))
        ->map(static fn ($item): string => $item->path())
        ->toArray();

    $this->hooks = fn (): array => $this::getContainer()->get(HookLog::class)->hooks;

    $this->saved = fn (): array => FileUploadFixtures::savedState($this::getContainer()->get(HookLog::class));
});

describe('acceptUploadedFile()', function (): void {
    it('holds a good file in the temporary store, puts its token in state under the file key, runs the update hooks and answers what it took', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), 'document', 'k1', FileUploadFixtures::file('pixel.png', 'Photo.png'));

        $token = $tester->component()->data['document']['k1'];
        $stored = $this::getContainer()->get(TemporaryUploads::class)->resolve($token, 'data.document');

        expect(($this->result)($tester))->toBe(['ok' => true, 'fileKey' => 'k1', 'name' => 'Photo.png', 'size' => 90, 'type' => 'image/png'])
            ->and(($this->hooks)())->toBe(['document updated'])
            ->and($tester->component()->data['document'])->toBe(['k1' => $token])
            ->and(($this->temporaryFiles)())->toEqualCanonicalizing([$stored->getPath(), $stored->getPath() . '.json'])
            ->and($this->primary->read($stored->getPath()))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')));
    });

    it('takes nothing while it is disabled', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), 'locked', 'k1', FileUploadFixtures::file('pixel.png'));

        expect(($this->result)($tester))->toBeNull()
            ->and($tester->component()->data['locked'])->toBe([])
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('answers the error PHP gave for a file the browser could not deliver, writing nothing', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), 'document', 'k1', FileUploadFixtures::file('pixel.png', error: UPLOAD_ERR_PARTIAL));

        expect(($this->result)($tester))->toBe(['ok' => false, 'message' => 'The file "pixel.png" was only partially uploaded.'])
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it("refuses a file one of the field's file constraints fails, with that constraint's message, before anything is written", function (string $field, string $fixture, string $clientName, string $message): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), $field, 'k1', FileUploadFixtures::file($fixture, $clientName, ''));

        expect(($this->result)($tester))->toBe(['ok' => false, 'message' => $message])
            ->and($tester->component()->data[$field])->toBe([])
            ->and(($this->hooks)())->toBe([])
            ->and(($this->temporaryFiles)())->toBe([]);
    })->with([
        'a type outside the accepted list, though named and sent as one inside it' => ['document', 'note.txt', 'scan.png', 'The mime type of the file is invalid ("text/plain"). Allowed mime types are "image/png".'],
        'a file over the maximum size' => ['document', 'large.png', 'large.png', 'The file is too large (3.12 KiB). Allowed maximum size is 1 KiB.'],
        'a file under the minimum size' => ['floored', 'pixel.png', 'pixel.png', 'The file is too small (0.09 KiB). Minimum size is 2 KiB.'],
        'an image of another ratio than the one allowed' => ['ratio', 'pixel.png', 'pixel.png', 'The image ratio is too small (1). Minimum ratio expected is 2.'],
        'an image of none of the ratios allowed' => ['ratios', 'wide.png', 'wide.png', 'This value should satisfy at least one of the following constraints: [1] The image ratio is too small (2). Minimum ratio expected is 3. [2] The image ratio is too small (2). Minimum ratio expected is 4.'],
        'a file that is not an image, on a field that allows a ratio' => ['ratio', 'note.txt', 'note.txt', 'This file is not a valid image.'],
        "a file the user's own image constraint refuses" => ['narrow', 'wide.png', 'wide.png', 'The image width is too big (2px). Allowed maximum width is 1px.'],
        "a type outside the list, with the field's own message" => ['typed', 'note.txt', 'note.txt', 'Only PNG images.'],
    ]);

    it('takes an image of the ratio allowed, however the ratio is spelled', function (string $field): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), $field, 'k1', FileUploadFixtures::file('wide.png'));

        expect(($this->result)($tester))->toMatchArray(['ok' => true]);
    })->with([
        'a colon' => ['ratio'],
        'a slash' => ['slashedRatio'],
        'a bare number' => ['numericRatio'],
    ]);

    it('takes uploads past the file limit, which only a save enforces, and replaces the entry an upload under a used key held', function (): void {
        $tester = $this->createLiveComponent(GalleryForm::class);

        foreach (['k1', 'k2', 'k2'] as $fileKey) {
            ($this->upload)($tester, 'capped', $fileKey, FileUploadFixtures::file('pixel.png'));

            expect(($this->result)($tester))->toMatchArray(['ok' => true]);
        }

        expect(array_keys($tester->component()->data['capped']))->toBe(['k1', 'k2'])
            ->and(($this->temporaryFiles)())->toHaveCount(6);
    });
});

describe('hydrateFiles()', function (): void {
    it('keeps only the stored paths the storage still holds when the record fills the form, and every non-blank one when it fetches no file information', function (): void {
        $this->primary->write('kept.png', file_get_contents(FileUploadFixtures::path('pixel.png')));

        $tester = $this->createLiveComponent(UploadForm::class, ['record' => [
            'document' => ['a' => 'kept.png', 'b' => 'gone.png', 'c' => '', 'd' => 5],
            'fetchless' => ['a' => 'kept.png', 'b' => 'gone.png', 'c' => ''],
        ]]);

        expect($tester->component()->data)->toMatchArray([
            'document' => ['a' => 'kept.png'],
            'fetchless' => ['a' => 'kept.png', 'b' => 'gone.png'],
        ]);
    });

    it('leaves a path a live request writes, though the storage lacks it', function (): void {
        $this->createLiveComponent(UploadForm::class)
            ->fillForm(['document' => 'ghost.png'])
            ->assertFormSet(['document' => 'ghost.png']);
    });
});

describe('saveUploadedFiles()', function (): void {
    it('stores each upload of the field on its storage, deleting the temporary file, and passes a stored path through', function (): void {
        ($this->upload)($this->createLiveComponent(SavingUploadForm::class), 'document', 'k1', FileUploadFixtures::file('pixel.png', 'Photo.png'))
            ->fillForm(['rooted' => 'kept/a.png'])
            ->call('save');

        $saved = ($this->saved)();

        expect($saved)->toMatchArray(['rooted' => 'kept/a.png', 'documentName' => 'Photo.png'])
            ->and($saved['document'])->toMatch('#^docs/[0-9A-Z]{26}\.png$#')
            ->and($this->primary->read($saved['document']))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')))
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('stores an upload inside a repeater item, which the renders before the save leave to it', function (): void {
        $tester = $this->createLiveComponent(RepeatedAttachmentForm::class);
        $item = array_key_first($tester->component()->data['items']);

        ($this->upload)($tester, "items.{$item}.attachment", 'k1', FileUploadFixtures::file('pixel.png'));
        $tester->refresh()->call('save');

        $attachment = ($this->saved)()['items'][0]['attachment'];

        expect($attachment)->toMatch('#^[0-9A-Z]{26}\.png$#')
            ->and($this->primary->fileExists($attachment))->toBeTrue()
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('drops a token minted for another field and one whose bytes are gone', function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'held', 'k1', FileUploadFixtures::file('pixel.png'));
        ($this->upload)($tester, 'preserved', 'k2', FileUploadFixtures::file('pixel.png'));

        $foreign = $tester->component()->data['held']['k1'];
        $vanished = $tester->component()->data['preserved']['k2'];
        $this->primary->delete('sylament-tmp/' . explode(':', $vanished)[2]);

        $tester->fillForm(['rooted' => ['k1' => $foreign]])->call('save');

        expect(($this->saved)())->toMatchArray(['rooted' => null, 'preserved' => null, 'held' => $foreign])
            ->and(($this->storedFiles)($this->primary))->toBe([]);
    });

    it('drops blank entries, keeps a stored path of "0", and saves an empty field as empty', function (): void {
        $this->createLiveComponent(SavingUploadForm::class)
            ->fillForm(['album' => ['0', '', 'kept/b.png']])
            ->call('save');

        expect(($this->saved)())->toMatchArray([
            'album' => ['0', 'kept/b.png'],
            'ordered' => [],
            'rooted' => null,
        ]);
    });

    it('leaves the token in state and stores nothing while it stores no files', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(SavingUploadForm::class), 'held', 'k1', FileUploadFixtures::file('pixel.png'));
        $token = $tester->component()->data['held']['k1'];

        $tester->call('save');

        expect(($this->saved)()['held'])->toBe($token)
            ->and(($this->temporaryFiles)())->toHaveCount(2)
            ->and(($this->storedFiles)($this->primary))->toBe([]);
    });

    it('hands the stored set to the reorder callback only while the field is reorderable, read through its closure', function (): void {
        $this->createLiveComponent(SavingUploadForm::class)
            ->fillForm(['ordered' => ['one.png', 'two.png'], 'unordered' => ['one.png', 'two.png']])
            ->call('save');

        expect(($this->saved)())->toMatchArray([
            'ordered' => ['two.png', 'one.png'],
            'unordered' => ['one.png', 'two.png'],
        ])
            ->and(array_values(array_filter(($this->hooks)(), static fn (string $hook): bool => str_starts_with($hook, 'reordered'))))->toBe(['reordered']);
    });

    it('stores what a store step of its own answers, deleting the temporary file', function (): void {
        ($this->upload)($this->createLiveComponent(SavingUploadForm::class), 'replaced', 'k1', FileUploadFixtures::file('pixel.png', 'Photo.png'))->call('save');

        expect(($this->saved)()['replaced'])->toBe('replaced/Photo.png')
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('drops an upload no store step takes, deleting its temporary file rather than saving its token', function (): void {
        ($this->upload)($this->createLiveComponent(SavingUploadForm::class), 'discarded', 'k1', FileUploadFixtures::file('pixel.png'))->call('save');

        expect(($this->saved)()['discarded'])->toBeNull()
            ->and(($this->temporaryFiles)())->toBe([]);
    });
});

describe('saveUploadedFile()', function (): void {
    it('writes the bytes under the directory trimmed of slashes, or at the storage root, by a generated name, once', function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'document', 'k1', FileUploadFixtures::file('pixel.png'));
        ($this->upload)($tester, 'rooted', 'k2', FileUploadFixtures::file('pixel.png'));
        $tester->call('save');

        $saved = ($this->saved)();

        expect($saved['document'])->toMatch('#^docs/[0-9A-Z]{26}\.png$#')
            ->and($saved['rooted'])->toMatch('#^[0-9A-Z]{26}\.png$#')
            ->and($this->primary->read($saved['rooted']))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')));

        $tester->call('save');

        expect(($this->saved)())->toMatchArray(['document' => $saved['document'], 'rooted' => $saved['rooted']])
            ->and(($this->storedFiles)($this->primary))->toEqualCanonicalizing([$saved['document'], $saved['rooted']]);
    });

    it("stores under the client's own name when it preserves names, and under the name a generator of its own gives", function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'preserved', 'k1', FileUploadFixtures::file('pixel.png', 'Photo.png'));
        ($this->upload)($tester, 'named', 'k2', FileUploadFixtures::file('pixel.png', 'Photo.png'));
        $tester->call('save');

        expect(($this->saved)())->toMatchArray(['preserved' => 'Photo.png', 'named' => 'named-Photo.png'])
            ->and(($this->storedFiles)($this->primary))->toEqualCanonicalizing(['Photo.png', 'named-Photo.png']);
    });

    it('refuses to store a file its name generator names nothing', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(SavingUploadForm::class), 'nameless', 'k1', FileUploadFixtures::file('pixel.png'));

        expect(fn (): Testable => $tester->call('save'))->toThrow(LogicException::class, 'The [nameless] field could not name the file to store');
    });

    it('writes a private file privately, and leaves an unset visibility to the storage', function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'private', 'k1', FileUploadFixtures::file('pixel.png'));
        ($this->upload)($tester, 'rooted', 'k2', FileUploadFixtures::file('pixel.png'));
        $tester->call('save');

        $saved = ($this->saved)();

        expect($this->primary->visibility($saved['private']))->toBe('private')
            ->and($this->primary->visibility($saved['rooted']))->toBe('public');
    });

    it('moves the temporary file onto a path of the storage it shares, and streams one across to another storage though asked to move', function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'moved', 'k1', FileUploadFixtures::file('pixel.png'));
        ($this->upload)($tester, 'elsewhere', 'k2', FileUploadFixtures::file('pixel.png'));
        $tester->call('save');

        $saved = ($this->saved)();

        expect($this->primary->read($saved['moved']))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')))
            ->and($this->secondary->read($saved['elsewhere']))->toBe(file_get_contents(FileUploadFixtures::path('pixel.png')))
            ->and(($this->temporaryFiles)())->toBe([]);
    });
});

describe('disk()', function (): void {
    it('stores onto the storage the field names and onto the default when it names none, and renders the stored file from it', function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'elsewhere', 'k1', FileUploadFixtures::file('pixel.png'));
        ($this->upload)($tester, 'rooted', 'k2', FileUploadFixtures::file('pixel.png'));
        $tester->call('save');

        $saved = ($this->saved)();
        $crawler = $tester->render()->crawler();

        expect(($this->storedFiles)($this->secondary))->toBe([$saved['elsewhere']])
            ->and(($this->storedFiles)($this->primary))->toBe([$saved['rooted']])
            ->and(FileUploadFixtures::values($crawler, 'data.elsewhere')['uploadedFiles'])->toBe(['k1' => ['name' => $saved['elsewhere'], 'size' => 90, 'type' => 'image/png', 'url' => null]])
            ->and(FileUploadFixtures::values($crawler, 'data.rooted')['uploadedFiles'])->toBe(['k2' => ['name' => $saved['rooted'], 'size' => 90, 'type' => 'image/png', 'url' => '/files/' . $saved['rooted']]]);
    });
});

describe('getUploadedFiles()', function (): void {
    beforeEach(function (): void {
        foreach (['photos/a.png', 'photos/b.png'] as $path) {
            $this->primary->write($path, file_get_contents(FileUploadFixtures::path('pixel.png')));
        }
    });

    it("renders each stored file's name, size, type and URLs, its name from the stored names, and null for an upload the browser holds", function (): void {
        $tester = $this->createLiveComponent(GalleryForm::class, ['record' => [
            'gallery' => ['a' => 'photos/a.png', 'b' => 'photos/b.png'],
            'galleryNames' => ['photos/a.png' => 'Holiday.png'],
            'closed' => ['a' => 'photos/a.png'],
            'downloads' => ['a' => 'photos/a.png'],
        ]]);
        ($this->upload)($tester, 'gallery', 'k3', FileUploadFixtures::file('pixel.png'));

        $crawler = $tester->render()->crawler();
        $single = $this->createLiveComponent(SavingUploadForm::class)
            ->fillForm(['document' => 'photos/a.png', 'documentName' => 'Photo.png'])
            ->render()
            ->crawler();

        expect(FileUploadFixtures::values($crawler, 'data.gallery')['uploadedFiles'])->toBe([
            'a' => ['name' => 'Holiday.png', 'size' => 90, 'type' => 'image/png', 'url' => '/files/photos/a.png', 'openableUrl' => '/open/photos/a.png'],
            'b' => ['name' => 'b.png', 'size' => 90, 'type' => 'image/png', 'url' => '/files/photos/b.png', 'openableUrl' => '/open/photos/b.png'],
            'k3' => null,
        ])
            ->and(FileUploadFixtures::values($crawler, 'data.closed')['uploadedFiles'])->toBe([
                'a' => ['name' => 'a.png', 'size' => 90, 'type' => 'image/png', 'url' => '/files/photos/a.png'],
            ])
            ->and(FileUploadFixtures::values($crawler, 'data.downloads')['uploadedFiles'])->toBe([
                'a' => ['name' => 'a.png', 'size' => 90, 'type' => 'image/png', 'url' => '/files/photos/a.png', 'downloadableUrl' => '/download/photos/a.png'],
            ])
            ->and(FileUploadFixtures::values($single, 'data.document')['uploadedFiles'])->toBe([
                '0' => ['name' => 'Photo.png', 'size' => 90, 'type' => 'image/png', 'url' => '/files/photos/a.png'],
            ]);
    });

    it('renders null for a file the storage no longer holds, no size or type without fetching, and no URL for a private file on a storage that cannot sign one', function (): void {
        $crawler = $this->createLiveComponent(UploadForm::class)
            ->fillForm(['kept' => 'photos/gone.png', 'fetchless' => 'photos/a.png', 'private' => 'photos/a.png'])
            ->render()
            ->crawler();

        expect(FileUploadFixtures::values($crawler, 'data.kept')['uploadedFiles'])->toBe(['0' => null])
            ->and(FileUploadFixtures::values($crawler, 'data.fetchless')['uploadedFiles'])->toBe(['0' => ['name' => 'a.png', 'size' => 0, 'type' => null, 'url' => null]])
            ->and(FileUploadFixtures::values($crawler, 'data.private')['uploadedFiles'])->toBe(['0' => ['name' => 'a.png', 'size' => 90, 'type' => 'image/png', 'url' => null]]);
    });

    it('renders what a file-information callback of its own answers, its URL sanitised', function (): void {
        $crawler = $this->createLiveComponent(GalleryForm::class, ['record' => ['described' => ['a' => 'photos/a.png']]])->render()->crawler();

        expect(FileUploadFixtures::values($crawler, 'data.described')['uploadedFiles'])->toBe([
            'a' => ['name' => 'Described photos/a.png', 'size' => 0, 'type' => 'text/plain', 'url' => null],
        ]);
    });

    it('is rendered rather than fetched, so the browser cannot call it', function (): void {
        $tester = ($this->callMethod)($this->createLiveComponent(GalleryForm::class, ['record' => ['closed' => ['a' => 'photos/a.png']]]), 'closed', 'getUploadedFiles', []);

        expect(($this->result)($tester))->toBeNull()
            ->and(FileUploadFixtures::values($tester->render()->crawler(), 'data.closed')['uploadedFiles'])->toHaveKey('a');
    });
});

describe('removeUploadedFile()', function (): void {
    it('removes an upload, deleting its temporary file, and answers its token', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), 'document', 'k1', FileUploadFixtures::file('pixel.png'));
        $token = $tester->component()->data['document']['k1'];

        ($this->callMethod)($tester, 'document', 'removeUploadedFile', ['fileKey' => 'k1']);

        expect(($this->result)($tester))->toBe($token)
            ->and($tester->component()->data['document'])->toBe([])
            ->and(($this->hooks)())->toBe(['document updated'])
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('removes a stored path and its stored name, leaving the bytes, and answers the path', function (string $field, string $namesField, mixed $names, mixed $removedNames): void {
        $this->primary->write('photos/a.png', 'x');

        $tester = $this->createLiveComponent(GalleryForm::class, ['record' => [
            $field => ['a' => 'photos/a.png'],
            $namesField => $names,
        ]]);

        ($this->callMethod)($tester, $field, 'removeUploadedFile', ['fileKey' => 'a']);

        expect(($this->result)($tester))->toBe('photos/a.png')
            ->and($tester->component()->data)->toMatchArray([$field => [], $namesField => $removedNames])
            ->and($this->primary->fileExists('photos/a.png'))->toBeTrue();
    })->with([
        'a multiple field, keeping a map of names' => ['gallery', 'galleryNames', ['photos/a.png' => 'Holiday.png'], []],
        'a single field, keeping one name' => ['cover', 'coverName', 'Holiday.png', null],
    ]);

    it('removes nothing and answers null for a disabled or undeletable field, or a key it does not hold', function (string $field, string $fileKey): void {
        $this->primary->write('kept.png', 'x');

        $tester = ($this->callMethod)(
            $this->createLiveComponent(UploadForm::class, ['record' => ['locked' => ['a' => 'kept.png'], 'kept' => ['a' => 'kept.png'], 'document' => ['a' => 'kept.png']]]),
            $field,
            'removeUploadedFile',
            ['fileKey' => $fileKey],
        );

        expect(($this->result)($tester))->toBeNull()
            ->and($tester->component()->data[$field])->toBe(['a' => 'kept.png']);
    })->with([
        'a disabled field' => ['locked', 'a'],
        'a field that is not deletable' => ['kept', 'a'],
        'a key it does not hold' => ['document', 'b'],
    ]);
});

describe('deleteUploadedFile()', function (): void {
    it('hands the delete callback the upload it removed, and answers nothing', function (): void {
        $tester = ($this->upload)($this->createLiveComponent(UploadForm::class), 'document', 'k1', FileUploadFixtures::file('pixel.png', 'Photo.png'));

        ($this->callMethod)($tester, 'document', 'deleteUploadedFile', ['fileKey' => 'k1']);

        expect(($this->result)($tester))->toBeNull()
            ->and(($this->hooks)())->toBe(['document updated', 'deleted: upload Photo.png'])
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('hands the delete callback the stored path it removed, leaving the bytes to it', function (): void {
        $this->primary->write('kept.png', 'x');

        $tester = ($this->callMethod)($this->createLiveComponent(UploadForm::class, ['record' => ['document' => ['a' => 'kept.png']]]), 'document', 'deleteUploadedFile', ['fileKey' => 'a']);

        expect(($this->hooks)())->toBe(['document updated', 'deleted: kept.png'])
            ->and($tester->component()->data['document'])->toBe([])
            ->and($this->primary->fileExists('kept.png'))->toBeTrue();
    });

    it('runs no delete callback when the removal is refused', function (): void {
        $this->primary->write('kept.png', 'x');

        $tester = ($this->callMethod)($this->createLiveComponent(UploadForm::class, ['record' => ['document' => ['a' => 'kept.png']]]), 'document', 'deleteUploadedFile', ['fileKey' => 'b']);

        expect(($this->hooks)())->toBe([])
            ->and($tester->component()->data['document'])->toBe(['a' => 'kept.png']);
    });
});

describe('reorderUploadedFiles()', function (): void {
    it('puts the files in the order the browser sends, a key it did not name first', function (): void {
        $tester = ($this->callMethod)(
            $this->createLiveComponent(GalleryForm::class)->fillForm(['gallery' => ['a' => 'a.png', 'b' => 'b.png', 'c' => 'c.png']]),
            'gallery',
            'reorderUploadedFiles',
            ['fileKeys' => ['c', 'a']],
        );

        expect($tester->component()->data['gallery'])->toBe(['b' => 'b.png', 'c' => 'c.png', 'a' => 'a.png']);
    });

    it('keeps the order for a field that is not reorderable, or a key that is not a string', function (string $field, array $fileKeys): void {
        $tester = ($this->callMethod)(
            $this->createLiveComponent(GalleryForm::class)->fillForm([$field => ['a' => 'a.png', 'b' => 'b.png']]),
            $field,
            'reorderUploadedFiles',
            ['fileKeys' => $fileKeys],
        );

        expect($tester->component()->data[$field])->toBe(['a' => 'a.png', 'b' => 'b.png']);
    })->with([
        'a field that is not reorderable' => ['fixed', ['b', 'a']],
        'a key that is not a string' => ['gallery', ['b', 1]],
    ]);
});

describe('collection constraints', function (): void {
    it('refuses at save a set larger than the field allows', function (): void {
        $this->createLiveComponent(GalleryForm::class)
            ->fillForm(['gallery' => ['a.png', 'b.png', 'c.png']])
            ->call('save')
            ->assertHasFormErrors(['gallery' => 'This collection should contain 2 elements or less.']);
    });

    it('shows its required error once beside the count error for an empty required field', function (): void {
        $host = $this->createLiveComponent(GalleryForm::class)->call('save')->component();

        expect($host->getErrorsForSchemaStatePath('data.gallery'))->toBe(['This value should not be blank.', 'This collection should contain 1 element or more.']);
    });

    it("runs a nested constraint over the stored set at save, never on the file at upload", function (): void {
        $tester = ($this->upload)($this->createLiveComponent(GalleryForm::class), 'brief', 'k1', FileUploadFixtures::file('pixel.png'));

        expect(($this->result)($tester))->toMatchArray(['ok' => true]);

        $tester->fillForm(['gallery' => ['a.png']])
            ->call('save')
            ->assertHasFormErrors(['brief' => 'This value is too long. It should have 5 characters or less.'])
            ->assertHasNoFormErrors(['gallery']);
    });
});

describe('preventFilePathTampering()', function (): void {
    it("refuses at save a stored path the record's current value does not hold", function (string $recordShape, bool $authorisesOwned): void {
        $this->createLiveComponent(GuardedUploadForm::class, ['recordShape' => $recordShape])
            ->fillForm(['document' => 'other.png'])
            ->call('save')
            ->assertHasFormErrors(['document' => 'The submitted file path is not authorised.']);

        $owned = $this->createLiveComponent(GuardedUploadForm::class, ['recordShape' => $recordShape])
            ->fillForm(['document' => GuardedUploadForm::OWNED])
            ->call('save');

        $authorisesOwned
            ? $owned->assertHasNoFormErrors()
            : $owned->assertHasFormErrors(['document' => 'The submitted file path is not authorised.']);
    })->with([
        'an array record' => ['array', true],
        'an object record' => ['object', true],
        'an object record whose property is uninitialised' => ['uninitialised', false],
        'no record' => ['none', false],
    ]);

    it('authorises nothing for a field the object record has no property for', function (): void {
        $this->createLiveComponent(GuardedUploadForm::class, ['recordShape' => 'object'])
            ->fillForm(['shared' => GuardedUploadForm::OWNED])
            ->call('save')
            ->assertHasFormErrors(['shared' => 'The submitted file path is not authorised.']);
    });

    it('admits a path its own callback allows, and passes any path on a field without the gate', function (): void {
        $this->createLiveComponent(GuardedUploadForm::class)
            ->fillForm(['shared' => 'public/a.png', 'open' => 'anything.png'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['shared' => 'private/a.png'])
            ->call('save')
            ->assertHasFormErrors(['shared' => 'The submitted file path is not authorised.']);
    });

    it("stores the field's own upload at save and lets the browser remove one, deleting its temporary file", function (): void {
        $tester = $this->createLiveComponent(GuardedUploadForm::class);
        ($this->upload)($tester, 'document', 'k1', FileUploadFixtures::file('pixel.png'));
        $tester->call('save')->assertHasNoFormErrors();

        ($this->upload)($tester, 'shared', 'k2', FileUploadFixtures::file('pixel.png'));
        $token = $tester->component()->data['shared']['k2'];
        ($this->callMethod)($tester, 'shared', 'removeUploadedFile', ['fileKey' => 'k2']);

        expect(($this->result)($tester))->toBe($token)
            ->and(($this->temporaryFiles)())->toBe([]);
    });

    it('renders nothing for a path it refuses, and refuses the browser its removal', function (): void {
        $this->primary->write('other.png', 'x');
        $this->primary->write(GuardedUploadForm::OWNED, 'x');

        $tester = $this->createLiveComponent(GuardedUploadForm::class)->fillForm(['document' => ['other.png', GuardedUploadForm::OWNED]]);
        $uploadedFiles = FileUploadFixtures::values($tester->render()->crawler(), 'data.document')['uploadedFiles'];

        expect($uploadedFiles[0])->toBeNull()
            ->and($uploadedFiles[1])->toMatchArray(['name' => 'owned.png']);

        ($this->callMethod)($tester, 'document', 'removeUploadedFile', ['fileKey' => '0']);

        expect(($this->result)($tester))->toBeNull()
            ->and($tester->component()->data['document'])->toBe(['other.png', GuardedUploadForm::OWNED]);
    });
});

describe('storeFileNamesIn()', function (): void {
    it("keeps each upload's original name beside the field at save, one name for a single field and a map by stored path for a multiple one", function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'document', 'k1', FileUploadFixtures::file('pixel.png', 'Photo.png'));
        ($this->upload)($tester, 'album', 'k2', FileUploadFixtures::file('pixel.png', 'One.png'));
        ($this->upload)($tester, 'album', 'k3', FileUploadFixtures::file('wide.png', 'Two.png'));
        $tester->call('save');

        $saved = ($this->saved)();

        expect($saved['documentName'])->toBe('Photo.png')
            ->and($saved['album'])->toHaveCount(2)
            ->and($saved['albumNames'])->toBe([$saved['album'][0] => 'One.png', $saved['album'][1] => 'Two.png'])
            ->and(array_keys($saved))->toEqualCanonicalizing([
                'document', 'documentName', 'rooted', 'preserved', 'named', 'private', 'moved', 'elsewhere', 'held',
                'discarded', 'replaced', 'nameless', 'album', 'albumNames', 'ordered', 'unordered', 'details',
            ]);
    });

    it('keeps the name beside a field nested under a state path, by a relative path or one a closure gives', function (): void {
        $tester = $this->createLiveComponent(SavingUploadForm::class);
        ($this->upload)($tester, 'details.scan', 'k1', FileUploadFixtures::file('pixel.png', 'Scan.png'));
        ($this->upload)($tester, 'details.snapshot', 'k2', FileUploadFixtures::file('pixel.png', 'Snap.png'));
        $tester->call('save');

        expect(($this->saved)()['details'])->toMatchArray([
            'scanName' => 'Scan.png',
            'snapshotName' => 'Snap.png',
        ]);
    });
});

describe('services', function (): void {
    it('cannot reach the temporary store or the storages when built with new, naming the field, while its file constraints still validate', function (): void {
        $field = new FileUpload()->name('document')->statePath('document')->acceptedFileTypes(['image/png']);
        new RecordMappingHost()->mapping($field)->getComponents();

        expect(fn (): TemporaryUploads => $field->getTemporaryUploads())->toThrow(LogicException::class, 'The [document] field was built with `new` and cannot upload files; register it as a service so the temporary-upload store reaches it.')
            ->and(fn (): FilesystemRegistry => $field->getFilesystemRegistry())->toThrow(LogicException::class, 'The [document] field was built with `new` and cannot upload files; register it as a service so the filesystem registry reaches it.')
            ->and($field->getValidator()->validate(FileUploadFixtures::file('note.txt'), $field->getUploadedFileConstraints()))->toHaveCount(1)
            ->and($field->getValidator()->validate(FileUploadFixtures::file('pixel.png'), $field->getUploadedFileConstraints()))->toHaveCount(0);
    });
});
