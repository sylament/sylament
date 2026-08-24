<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use InvalidArgumentException;
use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\Schema;
use Twig\Markup;

it("makes Filament's twelve avatar() calls, the three positions branching on the editor", function (): void {
    $avatar = FileUpload::make('avatar')->avatar();

    expect($avatar->isAvatar())->toBeTrue()
        ->and($avatar->getAcceptedFileTypes())->toBe(['image/*'])
        ->and($avatar->getImageAspectRatio())->toBe('1:1')
        ->and($avatar->getAutomaticallyCropImagesAspectRatio())->toBe('1:1')
        ->and($avatar->getAutomaticallyResizeImagesMode())->toBe('cover')
        ->and($avatar->shouldAutomaticallyUpscaleImagesWhenResizing())->toBeFalse()
        ->and($avatar->getAutomaticallyResizeImagesHeight())->toBe('500')
        ->and($avatar->getAutomaticallyResizeImagesWidth())->toBe('500')
        ->and($avatar->getLoadingIndicatorPosition())->toBe('center bottom')
        ->and($avatar->getPanelLayout())->toBe('compact circle')
        ->and($avatar->getRemoveUploadedFileButtonPosition())->toBe('center bottom')
        ->and($avatar->getUploadButtonPosition())->toBe('center bottom')
        ->and($avatar->getUploadProgressIndicatorPosition())->toBe('center bottom');

    $editable = FileUpload::make('avatar')->avatar()->imageEditor();

    expect($editable->getRemoveUploadedFileButtonPosition())->toBe('left bottom')
        ->and($editable->getUploadButtonPosition())->toBe('right bottom')
        ->and($editable->getUploadProgressIndicatorPosition())->toBe('right bottom');
});

it('accepts every image type under image()', function (): void {
    expect(FileUpload::make('cover')->image()->getAcceptedFileTypes())->toBe(['image/*']);
});

it('rests the panel layout at compact and the positions at right and left', function (): void {
    $upload = FileUpload::make('cover');

    expect($upload->getPanelLayout())->toBe('compact')
        ->and($upload->getLoadingIndicatorPosition())->toBe('right')
        ->and($upload->getRemoveUploadedFileButtonPosition())->toBe('left')
        ->and($upload->getUploadButtonPosition())->toBe('right')
        ->and($upload->getUploadProgressIndicatorPosition())->toBe('right')
        ->and($upload->isAvatar())->toBeFalse()
        ->and($upload->shouldAppendFiles())->toBeFalse()
        ->and($upload->getImagePreviewHeight())->toBeNull()
        ->and($upload->getMimeTypeMap())->toBe([]);
});

it('squares the item panel under a grid layout and leaves it alone otherwise', function (): void {
    expect(FileUpload::make('cover')->panelLayout('grid')->getItemPanelAspectRatio())->toBe(1)
        ->and(FileUpload::make('cover')->getItemPanelAspectRatio())->toBeNull()
        ->and(FileUpload::make('cover')->panelLayout('grid')->itemPanelAspectRatio('16:9')->getItemPanelAspectRatio())
        ->toEqualWithDelta(16 / 9, 0.0001);
});

it('normalises the panel aspect ratio to the colon spelling', function (): void {
    expect(FileUpload::make('cover')->panelAspectRatio('16/9')->getPanelAspectRatio())->toBe('16:9')
        ->and(FileUpload::make('cover')->panelAspectRatio('16:9')->getPanelAspectRatio())->toBe('16:9')
        ->and(FileUpload::make('cover')->panelAspectRatio('1.5')->getPanelAspectRatio())->toBe('1.5:1')
        ->and(FileUpload::make('cover')->panelAspectRatio('nonsense')->getPanelAspectRatio())->toBeNull()
        ->and(FileUpload::make('cover')->getPanelAspectRatio())->toBeNull();
});

it('carries the extension-to-mime overrides the browser cannot sniff', function (): void {
    expect(FileUpload::make('cover')->mimeTypeMap(['heic' => 'image/heic'])->getMimeTypeMap())
        ->toBe(['heic' => 'image/heic']);
});

it('appends files rather than prepending them when asked', function (): void {
    expect(FileUpload::make('cover')->appendFiles()->shouldAppendFiles())->toBeTrue();
});

it('carries the image preview height straight through to FilePond', function (): void {
    expect(FileUpload::make('cover')->imagePreviewHeight('250')->getImagePreviewHeight())->toBe('250');
});

it('rests the crop, resize, exif and editor families at their defaults', function (): void {
    $upload = FileUpload::make('cover');

    expect($upload->shouldAutomaticallyCropImagesToAspectRatio())->toBeFalse()
        ->and($upload->getAutomaticallyCropImagesAspectRatio())->toBeNull()
        ->and($upload->getAutomaticallyResizeImagesMode())->toBeNull()
        ->and($upload->getAutomaticallyResizeImagesHeight())->toBeNull()
        ->and($upload->getAutomaticallyResizeImagesWidth())->toBeNull()
        ->and($upload->shouldAutomaticallyUpscaleImagesWhenResizing())->toBeTrue()
        ->and($upload->shouldOrientImagesFromExif())->toBeTrue()
        ->and($upload->hasImageEditor())->toBeFalse()
        ->and($upload->isImageEditorExplicitlyEnabled())->toBeFalse()
        ->and($upload->hasCircleCropper())->toBeFalse()
        ->and($upload->canEditSvgs())->toBeTrue()
        ->and($upload->isSvgEditingConfirmed())->toBeFalse()
        ->and($upload->shouldAutomaticallyOpenImageEditorForAspectRatio())->toBeFalse()
        ->and($upload->getAutomaticallyOpenImageEditorForAspectRatio())->toBeNull()
        ->and($upload->getImageEditorViewportWidth())->toBeNull()
        ->and($upload->getImageEditorViewportHeight())->toBeNull()
        ->and($upload->getImageEditorMode())->toBe(1)
        ->and($upload->getImageEditorEmptyFillColor())->toBeNull();
});

it('crops to the first image aspect ratio only once the crop flag is on', function (): void {
    expect(FileUpload::make('cover')->imageAspectRatio(['16:9', '4:3'])->getAutomaticallyCropImagesAspectRatio())->toBeNull()
        ->and(FileUpload::make('cover')->imageAspectRatio(['16/9', '4:3'])->automaticallyCropImagesToAspectRatio()->getAutomaticallyCropImagesAspectRatio())->toBe('16:9')
        ->and(FileUpload::make('cover')->automaticallyCropImagesToAspectRatio()->getAutomaticallyCropImagesAspectRatio())->toBeNull();
});

it('derives the editor from the auto-open flag but reports the explicit switch separately', function (): void {
    $auto = FileUpload::make('cover')->imageAspectRatio('16:9')->automaticallyOpenImageEditorForAspectRatio();

    expect($auto->hasImageEditor())->toBeTrue()
        ->and($auto->isImageEditorExplicitlyEnabled())->toBeFalse()
        ->and($auto->getAutomaticallyOpenImageEditorForAspectRatio())->toEqualWithDelta(16 / 9, 0.0001);
});

it('refuses the auto-open editor on a multiple field, without a ratio, or with several', function (): void {
    expect(fn (): bool => FileUpload::make('cover')->multiple()->imageAspectRatio('1:1')->automaticallyOpenImageEditorForAspectRatio()->hasImageEditor())
        ->toThrow(InvalidArgumentException::class, 'cannot be used when [multiple()] is enabled');
    expect(fn (): bool => FileUpload::make('cover')->automaticallyOpenImageEditorForAspectRatio()->hasImageEditor())
        ->toThrow(InvalidArgumentException::class, 'requires [imageAspectRatio()] to be set with a single aspect ratio');
    expect(fn (): bool => FileUpload::make('cover')->imageAspectRatio(['1:1', '4:3'])->automaticallyOpenImageEditorForAspectRatio()->hasImageEditor())
        ->toThrow(InvalidArgumentException::class, 'has multiple allowed aspect ratios');
});

it('accepts only editor modes 1, 2 and 3', function (): void {
    expect(FileUpload::make('cover')->imageEditorMode(3)->getImageEditorMode())->toBe(3);
    expect(fn (): FileUpload => FileUpload::make('cover')->imageEditorMode(0))
        ->toThrow(InvalidArgumentException::class, 'Mode 0 is not supported');
});

it('derives the editor viewport from the resize target, then the crop ratio, then the setting', function (): void {
    $resized = FileUpload::make('cover')->automaticallyResizeImagesToWidth('500')->automaticallyResizeImagesToHeight('500');
    $cropped = FileUpload::make('cover')->imageAspectRatio('16:9')->automaticallyCropImagesToAspectRatio();
    $configured = FileUpload::make('cover')->imageEditorViewportWidth(800)->imageEditorViewportHeight(600);

    expect($resized->getImageEditorViewportWidth())->toBe(360)
        ->and($resized->getImageEditorViewportHeight())->toBe(360)
        ->and($cropped->getImageEditorViewportWidth())->toBe(16)
        ->and($cropped->getImageEditorViewportHeight())->toBe(9)
        ->and($configured->getImageEditorViewportWidth())->toBe(800)
        ->and($configured->getImageEditorViewportHeight())->toBe(600);
});

it('carries the editor settings through their closures', function (): void {
    $upload = FileUpload::make('cover')
        ->imageEditor(fn (FileUpload $component): bool => $component->getName() === 'cover')
        ->circleCropper()
        ->editableSvgs(false)
        ->confirmSvgEditing()
        ->orientImagesFromExif(false)
        ->imageEditorEmptyFillColor('#ffffff');

    expect($upload->hasImageEditor())->toBeTrue()
        ->and($upload->hasCircleCropper())->toBeTrue()
        ->and($upload->canEditSvgs())->toBeFalse()
        ->and($upload->isSvgEditingConfirmed())->toBeTrue()
        ->and($upload->shouldOrientImagesFromExif())->toBeFalse()
        ->and($upload->getImageEditorEmptyFillColor())->toBe('#ffffff');
});

it('keys the editor aspect-ratio options by label, with Free as NaN, and hides a lone option', function (): void {
    $options = FileUpload::make('cover')
        ->imageEditorAspectRatioOptions([null, '16:9', '4/3', '16:9', 'nonsense'])
        ->getImageEditorAspectRatioOptionsForJs();

    expect(array_keys($options))->toBe(['Free', '16:9', '4:3'])
        ->and($options['Free'])->toBe('NaN')
        ->and($options['16:9'])->toEqualWithDelta(16 / 9, 0.0001)
        ->and($options['4:3'])->toEqualWithDelta(4 / 3, 0.0001);

    expect(FileUpload::make('cover')->imageEditorAspectRatioOptions(['1:1'])->getImageEditorAspectRatioOptionsForJs())->toBe([])
        ->and(FileUpload::make('cover')->getImageEditorAspectRatioOptionsForJs())->toBe([]);

    $withCrop = FileUpload::make('cover')
        ->imageEditorAspectRatioOptions([null])
        ->imageAspectRatio('1:1')
        ->automaticallyCropImagesToAspectRatio()
        ->getImageEditorAspectRatioOptionsForJs();

    expect(array_keys($withCrop))->toBe(['Free', '1:1']);
});

it("lists Filament's thirteen editor actions in three groups with their aliases, methods and params", function (): void {
    $actions = FileUpload::make('cover')->getImageEditorActions();

    expect(array_keys($actions))->toBe(['zoom', 'move', 'transform'])
        ->and(array_sum(array_map(count(...), $actions)))->toBe(13)
        ->and($actions['zoom'][0]['label'])->toBe('Drag mode "move"')
        ->and($actions['zoom'][0]['alias'])->toBe(FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_MOVE)
        ->and($actions['zoom'][0]['method'])->toBe('editorSetDragMode')
        ->and($actions['zoom'][0]['params'])->toBe(['mode' => 'move'])
        ->and($actions['zoom'][0]['icon'])->toBeInstanceOf(Markup::class)
        ->and($actions['zoom'][2]['icon'])->toBe('heroicons:magnifying-glass-plus-20-solid')
        ->and($actions['zoom'][2]['params'])->toBe(['ratio' => 0.1])
        ->and($actions['move'][0]['params'])->toBe(['x' => -10, 'y' => 0])
        ->and($actions['transform'][0]['params'])->toBe(['degrees' => -90])
        ->and($actions['transform'][2]['method'])->toBe('editorFlip')
        ->and($actions['transform'][2]['params'])->toBe(['axis' => 'x']);
});

it('refuses a circle cropper on an accept list that admits no PNG', function (): void {
    expect(FileUpload::make('avatar')->image()->circleCropper()->hasCircleCropper())->toBeTrue()
        ->and(FileUpload::make('avatar')->acceptedFileTypes(['image/png'])->circleCropper()->hasCircleCropper())->toBeTrue()
        ->and(FileUpload::make('avatar')->circleCropper()->hasCircleCropper())->toBeTrue();

    expect(fn (): bool => FileUpload::make('avatar')->acceptedFileTypes(['image/jpeg'])->circleCropper()->hasCircleCropper())
        ->toThrow(InvalidArgumentException::class, 'must admit image/png or image/*');
});

it('asks for the transform only when a crop ratio or a resize target is set', function (): void {
    $wire = function (FileUpload $upload): FileUpload {
        Schema::make(new FieldRenderHost())->statePath('data')->components([$upload])->getComponents();

        return $upload;
    };

    expect($wire(FileUpload::make('cover'))->getExtraTemplateData()['transformImages'])->toBeFalse()
        ->and($wire(FileUpload::make('cover')->automaticallyResizeImagesToWidth('800'))->getExtraTemplateData()['transformImages'])->toBeTrue()
        ->and($wire(FileUpload::make('avatar')->avatar())->getExtraTemplateData()['transformImages'])->toBeTrue();
});
