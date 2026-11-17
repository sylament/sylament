<?php

declare(strict_types=1);

use Sylament\Forms\Components\FileUpload;
use Sylament\Tests\Fixtures\Forms\FileUploadFixtures;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Forms\UploadForm;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Twig\Error\RuntimeError;

describe('render', function (): void {
    it('hands the controller its value map, with the file input inside a pond container no morph touches', function (): void {
        $crawler = $this->renderSchemaComponent(FileUpload::make('document')->label('Document'));

        expect(FileUploadFixtures::values($crawler, 'data.document'))->toBe([
            'key' => 'schema.document',
            'statePath' => 'data.document',
            'state' => [],
            'uploadedFiles' => [],
            'multiple' => false,
            'disabled' => false,
            'deletable' => true,
            'reorderable' => false,
            'pasteable' => true,
            'previewable' => true,
            'downloadable' => false,
            'openable' => false,
            'avatar' => false,
            'appendFiles' => false,
            'maxFileSize' => (int) UploadedFile::getMaxFilesize(),
            'mimeTypeMap' => [],
            'transformImages' => false,
            'panelLayout' => 'compact',
            'loadingIndicatorPosition' => 'right',
            'removeUploadedFileButtonPosition' => 'left',
            'uploadButtonPosition' => 'right',
            'uploadProgressIndicatorPosition' => 'right',
            'imageEditor' => false,
            'imageEditorExplicitlyEnabled' => false,
            'circleCropper' => false,
            'imageEditorMode' => 1,
            'confirmSvgEditing' => false,
            'downloadActionLabel' => 'Download',
            'openActionLabel' => 'Open in new tab',
            'confirmSvgEditingMessage' => "Editing SVG files is not recommended as it can result in quality loss when scaling.\nAre you sure you want to continue?",
            'disabledSvgEditingMessage' => 'Editing SVG files is disabled as it can result in quality loss when scaling.',
        ])
            ->and($crawler)->toContainElement('[id="' . $crawler->filter(Selectors::fileUpload('data.document') . '[role="group"]')->attr('aria-labelledby') . '"]', 'Document')
            ->and($crawler)->toContainElement(Selectors::FILE_UPLOAD_POND . '[data-live-ignore] input[type="file"]:not([multiple])')
            ->and($crawler)->toContainElement(Selectors::FILE_UPLOAD . ' [role="alert"][hidden]');
    });

    it('renders its three maps as JSON objects even when empty, the accepted types as a list and the size ceiling as a number', function (): void {
        $values = FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('document')->acceptedFileTypes(['png' => 'image/png'])), 'data.document', decode: false);

        expect($values)->toMatchArray([
            'state' => '{}',
            'uploadedFiles' => '{}',
            'mimeTypeMap' => '{}',
            'acceptedFileTypes' => '["image\/png"]',
            'maxFileSize' => (string) (int) UploadedFile::getMaxFilesize(),
        ]);
    });

    it('lists the accepted types from any iterable', function (): void {
        $types = (static function (): Generator {
            yield 'png' => 'image/png';
            yield 'jpeg' => 'image/jpeg';
        })();

        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('document')->acceptedFileTypes($types)), 'data.document', decode: false))
            ->toMatchArray(['acceptedFileTypes' => '["image\/png","image\/jpeg"]']);
    });

    it('accepts only images once it is an image field', function (): void {
        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('photo')->image()), 'data.photo'))
            ->toMatchArray(['acceptedFileTypes' => ['image/*']]);
    });

    it('hands the controller every presentation setting it is given', function (): void {
        $crawler = $this->renderSchemaComponent(
            FileUpload::make('gallery')
                ->multiple()
                ->appendFiles()
                ->deletable(false)
                ->downloadable()
                ->openable()
                ->reorderable()
                ->pasteable(false)
                ->previewable(false)
                ->maxParallelUploads(2)
                ->placeholder('Drop photos here')
                ->imagePreviewHeight('200px')
                ->panelAspectRatio('16/9')
                ->itemPanelAspectRatio('4:1')
                ->panelLayout('integrated')
                ->loadingIndicatorPosition('left')
                ->removeUploadedFileButtonPosition('right')
                ->uploadButtonPosition('left')
                ->uploadProgressIndicatorPosition('left')
                ->mimeTypeMap(['heic' => 'image/heic'])
                ->alignCenter(),
        );

        expect(FileUploadFixtures::values($crawler, 'data.gallery'))->toMatchArray([
            'multiple' => true,
            'appendFiles' => true,
            'deletable' => false,
            'downloadable' => true,
            'openable' => true,
            'reorderable' => true,
            'pasteable' => false,
            'previewable' => false,
            'maxParallelUploads' => 2,
            'placeholder' => 'Drop photos here',
            'imagePreviewHeight' => '200px',
            'panelAspectRatio' => '16:9',
            'itemPanelAspectRatio' => 4,
            'panelLayout' => 'integrated',
            'loadingIndicatorPosition' => 'left',
            'removeUploadedFileButtonPosition' => 'right',
            'uploadButtonPosition' => 'left',
            'uploadProgressIndicatorPosition' => 'left',
            'mimeTypeMap' => ['heic' => 'image/heic'],
        ])
            ->and($crawler)->toContainElement(Selectors::fileUpload('data.gallery') . SupportSelectors::align('center'))
            ->and($crawler)->toContainElement(Selectors::FILE_UPLOAD_POND . ' input[type="file"][multiple]');
    });

    it('gives a grid layout a square item panel unless one is set', function (): void {
        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('gallery')->panelLayout('grid')), 'data.gallery'))
            ->toMatchArray(['panelLayout' => 'grid', 'itemPanelAspectRatio' => 1]);
    });

    it("caps what the browser sends at the smaller of the field's size and PHP's ceiling, and floors it in FilePond's unit", function (?int $maxSize, int $maxFileSize): void {
        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('document')->maxSize($maxSize)->minSize(100)), 'data.document'))
            ->toMatchArray(['maxFileSize' => $maxFileSize, 'minSize' => '100KB']);
    })->with([
        'a field size under the ceiling' => [2, 2048],
        "a field size over PHP's ceiling" => [PHP_INT_MAX >> 20, (int) UploadedFile::getMaxFilesize()],
        'no field size' => [null, (int) UploadedFile::getMaxFilesize()],
    ]);

    it('names its label in the message the browser shows past the file limit', function (): void {
        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('photos')->label('Photos')->multiple()->maxFiles(3)), 'data.photos'))
            ->toMatchArray(['maxFiles' => 3, 'maxFilesMessage' => 'The Photos field must not have more than 3 items.']);
    });

    it('carries the link colour policy on the pond container, whose label action reads its text slots', function (): void {
        expect($this->renderSchemaComponent(FileUpload::make('document')))
            ->toContainElement(Selectors::FILE_UPLOAD_POND . SupportSelectors::COLORED . SupportSelectors::color('primary') . SupportSelectors::textShades(700, 400) . '[data-live-ignore]');
    });
});

describe('live updates', function (): void {
    it('re-renders the state and the payload after an upload, while the pond stays out of the morph', function (): void {
        $tester = $this->createLiveComponent(UploadForm::class)->call(
            'uploadSchemaComponentFile',
            ['componentKey' => 'form.document', 'fileKey' => 'k1'],
            ['sylament_upload_k1' => FileUploadFixtures::file('pixel.png')],
        );

        $crawler = $tester->render()->crawler();
        $values = FileUploadFixtures::values($crawler, 'data.document');

        expect($values['state'])->toBe($tester->component()->data['document'])
            ->and($values['state']['k1'])->toStartWith('sylament-file:')
            ->and($values['uploadedFiles'])->toBe(['k1' => null])
            ->and($crawler)->toContainElement(Selectors::fileUpload('data.document') . ' ' . Selectors::FILE_UPLOAD_POND . '[data-live-ignore]');
    });
});

describe('avatar()', function (): void {
    it('renders the avatar layout with the image preset: a square crop resized to cover, in a circle', function (): void {
        $crawler = $this->renderSchemaComponent(FileUpload::make('avatar')->avatar());

        expect(FileUploadFixtures::values($crawler, 'data.avatar'))->toMatchArray([
            'avatar' => true,
            'acceptedFileTypes' => ['image/*'],
            'imageCropAspectRatio' => '1:1',
            'imageResizeMode' => 'cover',
            'imageResizeTargetWidth' => 500,
            'imageResizeTargetHeight' => 500,
            'imageResizeUpscale' => false,
            'transformImages' => true,
            'panelLayout' => 'compact circle',
            'loadingIndicatorPosition' => 'center bottom',
            'removeUploadedFileButtonPosition' => 'center bottom',
            'uploadButtonPosition' => 'center bottom',
            'uploadProgressIndicatorPosition' => 'center bottom',
        ])
            ->and($crawler)->toContainElement(Selectors::fileUpload('data.avatar') . '.sy-fo-file-upload-avatar');
    });

    it('moves its buttons aside when the editor is on', function (): void {
        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('avatar')->avatar()->imageEditor()), 'data.avatar'))->toMatchArray([
            'removeUploadedFileButtonPosition' => 'left bottom',
            'uploadButtonPosition' => 'right bottom',
            'uploadProgressIndicatorPosition' => 'right bottom',
        ]);
    });
});

describe('image transforms', function (): void {
    it('crops to the first ratio only when asked, resizes to its target and has the browser transform the bytes', function (): void {
        $field = FileUpload::make('photo')
            ->imageAspectRatio(['16:9', '4:3'])
            ->automaticallyCropImagesToAspectRatio()
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyResizeImagesToWidth('800')
            ->automaticallyResizeImagesToHeight('450');

        expect(FileUploadFixtures::values($this->renderSchemaComponent($field), 'data.photo'))->toMatchArray([
            'imageCropAspectRatio' => '16:9',
            'imageResizeMode' => 'contain',
            'imageResizeTargetWidth' => 800,
            'imageResizeTargetHeight' => 450,
            'transformImages' => true,
        ]);
    });

    it('validates a ratio it is not asked to crop to, handing the browser no crop', function (): void {
        $values = FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('photo')->imageAspectRatio('16:9')), 'data.photo');

        expect($values)->toMatchArray(['transformImages' => false])
            ->and($values)->not->toHaveKey('imageCropAspectRatio');
    });

    it('tells the browser to skip EXIF orientation only when it is turned off', function (): void {
        expect(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('photo')), 'data.photo'))->not->toHaveKey('orientImagesFromExif')
            ->and(FileUploadFixtures::values($this->renderSchemaComponent(FileUpload::make('photo')->orientImagesFromExif(false)), 'data.photo'))->toMatchArray(['orientImagesFromExif' => false]);
    });
});

describe('image editor', function (): void {
    it('renders the editor as a dialog no morph touches only while the editor is on and the field is enabled', function (): void {
        $field = FileUpload::make('photo')
            ->imageEditor()
            ->imageEditorMode(2)
            ->imageEditorEmptyFillColor('#000000')
            ->imageEditorViewportWidth(300)
            ->imageEditorViewportHeight(200)
            ->editableSvgs(false)
            ->confirmSvgEditing();

        $crawler = $this->renderSchemaComponent($field);

        expect($crawler)->toContainElement(Selectors::FILE_UPLOAD_EDITOR . '[data-live-ignore]')
            ->and(FileUploadFixtures::values($crawler, 'data.photo'))->toMatchArray([
                'imageEditor' => true,
                'imageEditorExplicitlyEnabled' => true,
                'imageEditorMode' => 2,
                'imageEditorEmptyFillColor' => '#000000',
                'imageEditorViewportWidth' => 300,
                'imageEditorViewportHeight' => 200,
                'editableSvgs' => false,
                'confirmSvgEditing' => true,
            ])
            ->and($this->renderSchemaComponent(FileUpload::make('photo')))->not->toContainElement(Selectors::FILE_UPLOAD_EDITOR)
            ->and($this->renderSchemaComponent(FileUpload::make('photo')->imageEditor()->disabled()))->not->toContainElement(Selectors::FILE_UPLOAD_EDITOR);
    });

    it("renders the editor's fields, its thirteen tools each bound to its controller method, and its footer", function (): void {
        $crawler = $this->renderSchemaComponent(FileUpload::make('photo')->imageEditor());
        $editor = $crawler->filter(Selectors::FILE_UPLOAD_EDITOR);

        expect($editor->filter('input[type="text"]')->each(static fn ($input): ?string => $input->attr('aria-label')))->toBe(['X', 'Y', 'Width', 'Height', 'Rotation'])
            ->and($editor)->toContainButtons(SupportSelectors::BUTTON_GROUP . ' button', [
                'Drag mode "move"', 'Drag mode "crop"', 'Zoom in', 'Zoom out', 'Zoom image to 100%',
                'Move image to left', 'Move image to right', 'Move image up', 'Move image down',
                'Rotate image to left', 'Rotate image to right', 'Flip image horizontally', 'Flip image vertically',
            ])
            ->and($editor->filter('button[aria-label="Zoom in"]')->attr('data-action'))->toBe('click->sylament--forms--file-upload#editorZoom')
            ->and($editor->filter('button[aria-label="Zoom in"]')->attr('data-sylament--forms--file-upload-ratio-param'))->toBe('0.1')
            ->and($editor->filter('button[aria-label="Flip image vertically"]')->attr('data-sylament--forms--file-upload-axis-param'))->toBe('y')
            ->and($editor->filter('.sy-fo-file-upload-editor-control-panel-footer button')->each(static fn ($button): string => $button->text()))->toBe(['Cancel', 'Reset', 'Save']);
    });

    it('offers a button per aspect ratio once it has two, dropping one it cannot read', function (): void {
        $editor = $this->renderSchemaComponent(FileUpload::make('photo')->imageEditor()->imageEditorAspectRatioOptions([null, '16/9', '1.5', 'wide']))
            ->filter(Selectors::FILE_UPLOAD_EDITOR);

        expect($editor)->toContainElement('.sy-fo-file-upload-editor-control-panel-group-title', 'Aspect ratios')
            ->and($editor->filter('[title^="Set aspect ratio to"]')->each(static fn ($button): array => [
                $button->attr('title'),
                $button->attr('data-sylament--forms--file-upload-ratio-param'),
            ]))->toBe([
                ['Set aspect ratio to Free', 'NaN'],
                ['Set aspect ratio to 16:9', (string) (16 / 9)],
                ['Set aspect ratio to 1.5', '1.5'],
            ])
            ->and($this->renderSchemaComponent(FileUpload::make('photo')->imageEditor()->imageEditorAspectRatioOptions(['16:9'])))
            ->not->toContainElement('.sy-fo-file-upload-editor-control-panel-group-title');
    });

    it('sizes its viewport from the resize target before the crop ratio', function (): void {
        $resized = FileUpload::make('photo')->imageEditor()->automaticallyResizeImagesToWidth('720')->automaticallyResizeImagesToHeight('480')->imageEditorViewportWidth(10);
        $cropped = FileUpload::make('photo')->imageEditor()->imageAspectRatio('16:9')->automaticallyCropImagesToAspectRatio()->imageEditorViewportWidth(10);

        expect(FileUploadFixtures::values($this->renderSchemaComponent($resized), 'data.photo'))->toMatchArray(['imageEditorViewportWidth' => 360, 'imageEditorViewportHeight' => 240])
            ->and(FileUploadFixtures::values($this->renderSchemaComponent($cropped), 'data.photo'))->toMatchArray(['imageEditorViewportWidth' => 16, 'imageEditorViewportHeight' => 9]);
    });

    it('refuses a mode Cropper.js cannot configure', function (): void {
        expect(fn (): FileUpload => FileUpload::make('photo')->imageEditorMode(4))->toThrow(InvalidArgumentException::class, 'must be either 1, 2 or 3');
    });

    it('opens itself for a lone ratio, as a crop-only editor saving first', function (): void {
        $crawler = $this->renderSchemaComponent(FileUpload::make('photo')->imageAspectRatio('2:1')->automaticallyOpenImageEditorForAspectRatio());

        expect(FileUploadFixtures::values($crawler, 'data.photo'))->toMatchArray([
            'imageEditor' => true,
            'imageEditorExplicitlyEnabled' => false,
            'automaticallyOpenImageEditorForAspectRatio' => 2,
        ])
            ->and($crawler->filter(Selectors::FILE_UPLOAD_EDITOR . '.sy-fo-file-upload-editor-crop-only button')->each(static fn ($button): string => $button->text()))->toBe(['Save', 'Cancel']);
    });

    it('refuses to open itself without exactly one ratio on a single field', function (Closure $field, string $message): void {
        expect(fn () => $this->renderSchemaComponent($field()))->toThrow(function (RuntimeError $error) use ($message): void {
            expect($error->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
                ->and($error->getPrevious()->getMessage())->toContain($message);
        });
    })->with([
        'a multiple field' => [fn (): FileUpload => FileUpload::make('photo')->multiple()->imageAspectRatio('2:1')->automaticallyOpenImageEditorForAspectRatio(), 'cannot be used when [multiple()] is enabled'],
        'no ratio' => [fn (): FileUpload => FileUpload::make('photo')->automaticallyOpenImageEditorForAspectRatio(), 'requires [imageAspectRatio()]'],
        'two ratios' => [fn (): FileUpload => FileUpload::make('photo')->imageAspectRatio(['2:1', '3:1'])->automaticallyOpenImageEditorForAspectRatio(), 'has multiple allowed aspect ratios'],
    ]);

    it('crops in a circle when the accepted types admit a PNG, and refuses to render one that admits none', function (?array $types, bool $renders): void {
        $field = FileUpload::make('photo')->imageEditor()->circleCropper();

        if ($types !== null) {
            $field->acceptedFileTypes($types);
        }

        if (!$renders) {
            expect(fn () => $this->renderSchemaComponent($field))->toThrow(function (RuntimeError $error): void {
                expect($error->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
                    ->and($error->getPrevious()->getMessage())->toContain('must admit image/png or image/*');
            });

            return;
        }

        $crawler = $this->renderSchemaComponent($field);

        expect(FileUploadFixtures::values($crawler, 'data.photo'))->toMatchArray(['circleCropper' => true])
            ->and($crawler)->toContainElement(Selectors::FILE_UPLOAD_EDITOR . '.sy-fo-file-upload-editor-circle-cropper');
    })->with([
        'no accepted types' => [null, true],
        'PNG' => [['image/png'], true],
        'any image' => [['image/*'], true],
        'JPEG only' => [['image/jpeg'], false],
    ]);
});
