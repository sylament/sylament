<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use Sylament\Forms\Components\FileUpload;

it('reduces avatar() to what ships: no crop, no resize, and therefore no aspect ratio', function (): void {
    // Filament's `avatar()` makes twelve calls, seven of them into the deferred crop and resize
    // plugins — `imageAspectRatio('1:1')` among them, which without client-side cropping would
    // refuse every non-square photo.
    $avatar = FileUpload::make('avatar')->avatar();

    expect($avatar->isAvatar())->toBeTrue()
        ->and($avatar->getAcceptedFileTypes())->toBe(['image/*'])
        ->and($avatar->getPanelLayout())->toBe('compact circle')
        ->and($avatar->getLoadingIndicatorPosition())->toBe('center bottom')
        ->and($avatar->getRemoveUploadedFileButtonPosition())->toBe('center bottom')
        ->and($avatar->getUploadButtonPosition())->toBe('center bottom')
        ->and($avatar->getUploadProgressIndicatorPosition())->toBe('center bottom')
        ->and($avatar->getImageAspectRatio())->toBeNull();
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
